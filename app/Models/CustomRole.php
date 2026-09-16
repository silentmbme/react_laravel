<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CustomRole extends Model { protected $fillable=['name','slug','is_staff','permissions','is_active']; protected $casts=['is_staff'=>'boolean','is_active'=>'boolean','permissions'=>'array']; public function users(){return $this->hasMany(User::class,'role','slug');} }
