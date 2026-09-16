<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LedgerEntry extends Model { protected $fillable=['financial_transaction_id','account','entry_type','amount','currency','base_amount','base_currency']; protected $casts=['amount'=>'decimal:2','base_amount'=>'decimal:2']; public function transaction(){return $this->belongsTo(FinancialTransaction::class,'financial_transaction_id');} }
