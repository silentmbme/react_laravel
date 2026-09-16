<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TaxConfiguration extends Model { protected $fillable=['tax_enabled','tax_name','tax_code','tax_rate','tax_type','country','region','inclusive','effective_from','effective_until','status','metadata']; protected $casts=['tax_enabled'=>'boolean','tax_rate'=>'decimal:4','inclusive'=>'boolean','status'=>'boolean','effective_from'=>'datetime','effective_until'=>'datetime','metadata'=>'array']; }
