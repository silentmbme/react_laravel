<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class   Category extends Model
{
     protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'meta_title',
        'meta_description',
        'icon',
        'image',
        'sort_order',
        'status',
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')
                    ->orderBy('sort_order');
    }
}
