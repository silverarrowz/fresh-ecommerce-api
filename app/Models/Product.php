<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;
    protected $fillable = [
        'title',
        'price',
        'price_old',
        'description',
        'stock',
        'category_id'
    ];

    protected $with = ['images', 'category'];

    protected $casts = [
        'tags' => 'array',
    ];

    public function images()
    {
        return $this->hasMany((ProductImage::class));
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
