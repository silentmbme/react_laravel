<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PayoutItem extends Model { protected $fillable=['payout_id','financial_transaction_id','amount','currency']; protected $casts=['amount'=>'decimal:2']; public function payout(){return $this->belongsTo(Payout::class);} public function financialTransaction(){return $this->belongsTo(FinancialTransaction::class);}}
