<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class FinancialAuditLog extends Model { protected $fillable=['actor_id','action','entity_type','entity_id','old_values','new_values','ip_address']; protected $casts=['old_values'=>'array','new_values'=>'array']; }
