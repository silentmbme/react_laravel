<?php
namespace App\Services;
use App\Models\Order;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RefundService {
    public function record(Order $order, string $amount, string $key, ?int $actorId=null, ?int $itemId=null, ?string $reason=null): Refund {
        return DB::transaction(function () use ($order,$amount,$key,$actorId,$itemId,$reason) {
            $order = Order::lockForUpdate()->findOrFail($order->id);
            if ($order->status !== 'paid') throw ValidationException::withMessages(['order'=>['Only paid orders can be refunded.']]);
            $refund = Refund::firstOrCreate(['idempotency_key'=>$key], ['refund_id'=>(string)Str::uuid(),'order_id'=>$order->id,'order_item_id'=>$itemId,'requested_by'=>$actorId,'amount'=>$amount,'currency'=>$order->currency,'status'=>'pending','reason'=>$reason]);
            if (!$refund->wasRecentlyCreated) return $refund;
            $remaining = bcsub((string)$order->total, (string)$order->refunded_total, 2);
            if (bccomp($amount, '0.00', 2) <= 0 || bccomp($amount, $remaining, 2) === 1) throw ValidationException::withMessages(['amount'=>['Refund amount exceeds the captured amount.']]);
            app(FinancialLedgerService::class)->recordRefund($order,$amount,'refund:'.$refund->refund_id,$itemId);
            $newTotal = bcadd((string)$order->refunded_total,$amount,2);
            $order->update(['refunded_total'=>$newTotal,'status'=>bccomp($newTotal,(string)$order->total,2)===0?'refunded':'partially_refunded']);
            $refund->update(['status'=>'completed','processed_at'=>now()]);
            return $refund->fresh();
        });
    }
}
