<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
      use SoftDeletes;

    protected $fillable = [
        'author_id',
        'category_id',
        'name',
        'slug',
        'short_description',
        'description',
        'thumbnail',
        'file',
        'file_size',
        'version',
        'demo_url',
        'status'
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function licenses()
    {
        return $this->hasMany(ProductLicense::class);
    }
}
