<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class CommissionTier extends Model {protected $fillable=['name','min_lifetime_sales','platform_fee_percent','is_active','sort_order'];protected $casts=['min_lifetime_sales'=>'decimal:2','platform_fee_percent'=>'decimal:2','is_active'=>'boolean'];}