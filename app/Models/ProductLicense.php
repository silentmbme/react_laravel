<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductLicense extends Model
{
      protected $fillable = [
        'product_id',
        'license_id',
        'price'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function license()
    {
        return $this->belongsTo(License::class);
    }
}
