<?php
namespace App\Services;
use App\Models\FinancialAuditLog;
use App\Models\FinancialTransaction;
use App\Models\LedgerEntry;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FinancialLedgerService {
    public function recordSale(Order $order): FinancialTransaction {
        return $this->record($order, 'SALE', (string)$order->total, 'sale:'.$order->id, [
            ['cash_clearing','debit',(string)$order->total], ['sales_revenue','credit',(string)$order->subtotal],
            ['tax_payable','credit',(string)$order->tax], ['fee_revenue','credit',bcadd((string)$order->buyer_fee_total,(string)$order->handling_fee,2)],
        ], ['payment_reference'=>$order->provider_reference, 'tax_amount'=>(string)$order->tax, 'net_amount'=>(string)$order->subtotal]);
    }
    public function recordSellerEarning(Order $order, int $sellerId, $item, string $gross, string $commission, string $net): FinancialTransaction {
        return $this->record($order, 'SELLER_EARNING', $net, 'seller_earning:'.$item->id, [
            ['seller_cost','debit',$gross], ['seller_payable','credit',$net], ['platform_commission','credit',$commission],
        ], ['order_item_id'=>$item->id,'seller_id'=>$sellerId,'product_id'=>$item->product_id,'fee_amount'=>$commission,'net_amount'=>$net]);
    }    public function recordRefund(Order $order, string $amount, string $key, ?int $itemId = null): FinancialTransaction {
        return $this->record($order, $amount === (string)$order->total ? 'REFUND' : 'PARTIAL_REFUND', bcmul($amount, '-1', 2), $key, [
            ['refunds','debit',$amount], ['cash_clearing','credit',$amount],
        ], ['order_item_id'=>$itemId,'net_amount'=>bcmul($amount, '-1', 2)]);
    }
    public function record(Order $order, string $type, string $amount, string $key, array $entries, array $extra=[]): FinancialTransaction {
        return DB::transaction(function () use ($order,$type,$amount,$key,$entries,$extra) {
            $transaction = FinancialTransaction::firstOrCreate(['idempotency_key'=>$key], array_merge(['transaction_id'=>(string)Str::uuid(),'order_id'=>$order->id,'customer_id'=>$order->user_id,'type'=>$type,'status'=>'completed','amount'=>$amount,'currency'=>$order->currency,'exchange_rate'=>'1.00000000','base_amount'=>$amount,'base_currency'=>'USD','fee_amount'=>'0.00','tax_amount'=>'0.00','net_amount'=>$amount,'payment_reference'=>$order->provider_reference], $extra));
            if (!$transaction->wasRecentlyCreated) return $transaction;
            foreach ($entries as [$account,$entryType,$entryAmount]) if (bccomp($entryAmount, '0.00', 2) !== 0) LedgerEntry::create(['financial_transaction_id'=>$transaction->id,'account'=>$account,'entry_type'=>$entryType,'amount'=>$entryAmount,'currency'=>$order->currency,'base_amount'=>$entryAmount,'base_currency'=>'USD']);
            FinancialAuditLog::create(['action'=>strtolower($type).'.recorded','entity_type'=>FinancialTransaction::class,'entity_id'=>$transaction->id,'new_values'=>['transaction_id'=>$transaction->transaction_id,'amount'=>$amount]]);
            return $transaction;
        });
    }
    public function sellerBalance(int $sellerId, string $currency='USD'): string {
        return (string) LedgerEntry::query()->join('financial_transactions','financial_transactions.id','=','ledger_entries.financial_transaction_id')->where('financial_transactions.seller_id',$sellerId)->where('ledger_entries.currency',$currency)->selectRaw("COALESCE(SUM(CASE WHEN entry_type = 'credit' THEN amount ELSE -amount END),0) balance")->value('balance');
    }
}
