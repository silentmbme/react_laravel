<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductVersion;
use App\Models\ProductReviewMessage;
use App\Models\User;
use App\Models\UserPermission;
use App\Notifications\MarketplaceNotification;
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
        $label = $values['decision'] === 'approved' ? 'approved' : ($values['decision'] === 'changes_requested' ? 'needs changes' : 'was rejected');
        $product->author?->notify(new MarketplaceNotification(['title' => 'Product review update','body' => $product->name.' '.$label.'.','url' => '/user/portfolio/'.$product->slug,'kind' => 'review']));
        return response()->json(['message' => 'Review decision saved.']);
    }

    public function message(Request $request, Product $product)
    {
        abort_unless($this->reviewer() || $product->author_id === Auth::id(), 403);
        $values = $request->validate(['message' => ['required', 'string', 'max:5000']]);
        $message = ProductReviewMessage::create(['product_id' => $product->id, 'user_id' => Auth::id(), 'message' => $values['message']]);
        if ($product->author_id !== Auth::id()) { $product->author?->notify(new MarketplaceNotification(['title' => 'Reviewer message','body' => 'You have a new review message about '.$product->name.'.','url' => '/user/portfolio/'.$product->slug,'kind' => 'review'])); }
        return $message;
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
        return User::whereIn('role', array_merge(['admin', 'reviewer'], \App\Models\CustomRole::where('is_staff', true)->where('is_active', true)->pluck('slug')->all()))->with(['permissions','customRole'])->get(['id', 'name', 'email', 'role']);
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
    }    public function updateQueue(Request $request)
    {
        abort_unless($this->reviewer(), 403);
        return ProductVersion::with(['product:id,name,slug,author_id,file,version','product.author:id,name'])->where('status','pending')->latest('submitted_at')->paginate(20);
    }

    public function decideUpdate(Request $request, ProductVersion $version)
    {
        abort_unless($this->reviewer(), 403);
        abort_unless($version->status === 'pending', 422, 'This update is not awaiting review.');
        $data = $request->validate(['decision' => ['required','in:approved,changes_requested,rejected']]);
        $product = $version->product;
        if ($data['decision'] === 'approved') {
            $snapshot = $version->snapshot;
            $product->update(['file' => $snapshot['file'], 'version' => $snapshot['version']]);
        }
        $version->update(['status' => $data['decision'], 'reviewed_at' => now()]);
        $version->submitted_by && User::find($version->submitted_by)?->notify(new MarketplaceNotification(['title' => 'Product update review','body' => $product->name.' update '.$data['decision'].'.','url' => '/user/portfolio','kind' => 'review']));
        return response()->json(['message' => $data['decision'] === 'approved' ? 'Update approved and live file replaced.' : 'Update review saved.', 'version' => $version->fresh()]);
    }
}
