<?php
namespace Tests\Feature;

use App\Models\Order;
use App\Models\TaxConfiguration;
use App\Models\User;
use App\Services\FinancialLedgerService;
use App\Services\RefundService;
use App\Services\TaxCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FinancialLedgerTest extends TestCase {
 use RefreshDatabase;
 public function test_tax_is_zero_when_no_configured_rule_applies(): void { $result=app(TaxCalculationService::class)->calculate('29.00','USD','US'); $this->assertSame('0.00',$result['amount']); $this->assertNull($result['snapshot']); }
 public function test_sale_ledger_is_idempotent_and_refund_does_not_delete_sale(): void {
  $user=User::factory()->create(); $order=Order::create(['public_id'=>(string)Str::uuid(),'user_id'=>$user->id,'status'=>'paid','currency'=>'USD','subtotal'=>'29.00','discount_total'=>'0.00','buyer_fee_total'=>'0.00','tax'=>'0.00','handling_fee'=>'0.00','total'=>'29.00','refunded_total'=>'0.00','paid_at'=>now()]);
  $ledger=app(FinancialLedgerService::class); $ledger->recordSale($order); $ledger->recordSale($order);
  $this->assertDatabaseCount('financial_transactions',1); $refund=app(RefundService::class)->record($order,'29.00',(string)Str::uuid(),$user->id);
  $this->assertSame('completed',$refund->status); $this->assertDatabaseCount('financial_transactions',2); $this->assertDatabaseHas('orders',['id'=>$order->id,'status'=>'refunded','refunded_total'=>'29.00']);
 }
}
