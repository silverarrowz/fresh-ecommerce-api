<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
   /** @use HasFactory<\Database\Factories\ProductFactory> */
   use HasFactory;
    protected $fillable = [
        'title', 'price', 'description', 'stock', 'category'
    ];

    protected $with = ['images'];

    public function images() {
        return $this->hasMany((ProductImage::class));
    }
}
