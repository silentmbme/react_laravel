<?php
namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\PurchaseVerification;
use Illuminate\Http\Request;

class PurchaseVerificationController extends Controller
{
    public function verify(Request $request)
    {
        $code = strtoupper(trim((string) $request->validate([
            'purchase_code' => ['required', 'string', 'max:100'],
        ])['purchase_code']));

        $record = PurchaseVerification::where('code_hash', hash('sha256', $code))
            ->with(['orderItem.product', 'orderItem.order'])
            ->first();

        if (! $record) {
            return response()->json(['valid' => false], 404);
        }

        $item = $record->orderItem;

        return response()->json([
            'valid' => true,
            'purchase' => [
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'license' => $item->license_name,
                'purchased_at' => $item->order?->paid_at,
                'support_included' => (bool) $record->support_until,
                'supported' => (bool) $record->support_until && $record->support_until->isFuture(),
                'support_until' => $record->support_until?->toDateString(),
            ],
        ]);
    }
}
