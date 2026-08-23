<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductReviewMessage;
use App\Models\User;
use App\Models\UserPermission;
use App\Services\MarketplaceSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductReviewController extends Controller
{
    private function reviewer(): bool
    {
        return in_array(Auth::user()->role, ['superadmin', 'admin'], true)
            || UserPermission::where('user_id', Auth::id())->where('permission', 'review.products')->exists();
    }

    private function superadmin(): void
    {
        abort_unless(Auth::user()->role === 'superadmin', 403, 'Superadmin access required.');
    }

    public function queue(Request $request)
    {
        abort_unless($this->reviewer(), 403);

        $values = $request->validate([
            'status' => ['nullable', 'in:pending,approved,changes_requested,rejected,all'],
            'search' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $status = $values['status'] ?? 'pending';
        $query = Product::with(['author:id,name', 'category:id,name'])->latest();

        if ($status !== 'all') {
            $query->where('status', $status === 'approved' ? 'published' : $status);
        }

        if (!empty($values['search'])) {
            $query->where('name', 'like', '%'.$values['search'].'%');
        }

        return $query->paginate($values['per_page'] ?? 20)->withQueryString();
    }

    public function detail(Product $product)
    {
        abort_unless($this->reviewer() || $product->author_id === Auth::id(), 403);
        return response()->json(['product' => $product->load(['author:id,name', 'category:id,name', 'reviews.reviewer:id,name', 'reviewMessages.user:id,name'])]);
    }

    public function decide(Request $request, Product $product)
    {
        abort_unless($this->reviewer(), 403);
        $values = $request->validate(['decision' => ['required', 'in:approved,rejected,changes_requested'], 'note' => ['required', 'string', 'max:5000']]);
        $status = $values['decision'] === 'approved' ? 'published' : ($values['decision'] === 'changes_requested' ? 'changes_requested' : 'rejected');
        ProductReview::create(['product_id' => $product->id, 'reviewer_id' => Auth::id(), 'decision' => $values['decision'], 'note' => $values['note']]);
        $product->update(['status' => $status]);
        return response()->json(['message' => 'Review decision saved.']);
    }

    public function message(Request $request, Product $product)
    {
        abort_unless($this->reviewer() || $product->author_id === Auth::id(), 403);
        $values = $request->validate(['message' => ['required', 'string', 'max:5000']]);
        return ProductReviewMessage::create(['product_id' => $product->id, 'user_id' => Auth::id(), 'message' => $values['message']]);
    }

    public function download(Product $product, MarketplaceSettings $settings)
    {
        abort_unless($this->reviewer(), 403);
        abort_unless($product->file, 404, 'Product file is unavailable.');

        $bucket = $settings->r2Bucket();
        abort_unless($bucket, 422, 'Cloud storage is not configured.');
        $disk = $settings->r2Disk();
        $command = $disk->getClient()->getCommand('GetObject', [
            'Bucket' => $bucket,
            'Key' => $product->file,
            'ResponseContentDisposition' => 'attachment; filename="'.basename($product->file).'"',
        ]);
        $request = $disk->getClient()->createPresignedRequest($command, '+10 minutes');

        return response()->json(['download_url' => (string) $request->getUri(), 'expires_in' => 600]);
    }

    public function reviewers()
    {
        $this->superadmin();
        return User::whereIn('role', ['admin', 'reviewer'])->with('permissions')->get(['id', 'name', 'email', 'role']);
    }

    public function permission(Request $request, User $user)
    {
        $this->superadmin();
        $values = $request->validate(['enabled' => ['required', 'boolean']]);
        if ($values['enabled']) {
            UserPermission::firstOrCreate(['user_id' => $user->id, 'permission' => 'review.products']);
        } else {
            UserPermission::where(['user_id' => $user->id, 'permission' => 'review.products'])->delete();
        }
        return response()->json(['message' => 'Permission updated.']);
    }
}
