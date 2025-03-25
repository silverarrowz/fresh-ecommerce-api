<?php

namespace App\Http\Controllers;

use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
//    public function destroy($id) {
//     $image = ProductImage::findOrFail($id);

//     if (Storage::disk('public')->exists($image->path)) {
//         Storage::disk('public')->delete($image->path);
//     }

//     $image->delete();

//     return response()->json(['message' => 'Image deleted successfully']);
//    }
}
