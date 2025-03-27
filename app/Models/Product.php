<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
   /** @use HasFactory<\Database\Factories\ProductFactory> */
   use HasFactory;
    protected $fillable = [
        'title', 'price', 'price_old', 'description', 'stock', 'category'
    ];

    protected $with = ['images'];

    protected $casts = [
        'tags' => 'array',
    ];

    public function images() {
        return $this->hasMany((ProductImage::class));
    }
}
