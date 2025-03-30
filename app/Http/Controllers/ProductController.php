<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Product::latest()->get());
    }

    public function getPaginatedProducts(Request $request)
    {
        $perPage = $request->input('per_page', 8);
        return response()->json(Product::paginate($perPage));
    }

    public function getLatest()
    {
        return response()->json(Product::latest()->take(10)->get());
    }

    public function getByTag(Request $request)
    {
        $tag = $request->input('tag');
        $products = Product::whereJsonContains('tags', $tag)->get();

        return response()->json($products);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'price_old' => 'nullable|numeric|min:0',
                'description' => 'required|string',
                'stock' => 'required|numeric|min:0|max:2000',
                'category' => 'required|string',
                'images' => 'nullable|array',
                'images.*' => 'image|max:2048',
            ]);

            $product = Product::create($validated);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    try {
                        $filename = Str::uuid() . '.webp';
                        $img = Image::read($image)->scale(700)->toWebp(90);
                        Storage::disk('public')->put("products/{$filename}", (string) $img);
                        $path = storage_path("app/public/products/{$filename}");
                        ImageOptimizer::optimize($path);

                        $product->images()->create([
                            'path' => "products/{$filename}",
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to process image: ' . $e->getMessage());
                        continue;
                    }
                }
            }

            $product->load('images');
            return response()->json([
                'product' => $product,
                'message' => 'Product created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Product creation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }

        return response()->json($product);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'price_old' => 'nullable|numeric|min:0',
                'description' => 'required|string',
                'stock' => 'required|numeric|min:0|max:2000',
                'category' => 'required|string',
                'images' => 'nullable|array',
                'images.*' => 'image|max:2048',
                'imagesToDelete' => 'nullable|json'
            ]);

            $product->update($validated);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    try {
                        $filename = Str::uuid() . '.webp';
                        $img = Image::read($image)->scale(700)->toWebp(90);
                        Storage::disk('public')->put("products/{$filename}", (string) $img);
                        $path = storage_path("app/public/products/{$filename}");
                        ImageOptimizer::optimize($path);

                        $product->images()->create([
                            'path' => "products/{$filename}",
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to process image: ' . $e->getMessage());
                        continue;
                    }
                }
            }

            if ($request->has('imagesToDelete')) {
                $imagesToDelete = json_decode($request->input('imagesToDelete'), true);
                if ($imagesToDelete && is_array($imagesToDelete)) {
                    foreach ($imagesToDelete as $imageId) {
                        $image = ProductImage::find($imageId);

                        if ($image) {
                            try {
                                if (Storage::disk('public')->exists($image->path)) {
                                    Storage::disk('public')->delete($image->path);
                                }
                                $image->delete();
                            } catch (\Exception $e) {
                                Log::error('Failed to delete image: ' . $e->getMessage());
                                continue;
                            }
                        }
                    }
                }
            }

            $product->load('images');
            return response()->json([
                'product' => $product,
                'message' => 'Product updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Product update failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }

        try {
            foreach ($product->images as $image) {
                if (Storage::disk('public')->exists($image->path)) {
                    Storage::disk('public')->delete($image->path);
                }
            }

            $product->delete();

            return response()->json([
                'message' => 'Product deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Product deletion failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to delete product',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
