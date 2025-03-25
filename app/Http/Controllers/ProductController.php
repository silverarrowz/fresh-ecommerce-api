<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Product::all());
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
                'description' => 'required|string',
                'stock' => 'required|numeric|min:0|max:2000',
                'category' => 'required|string',
                'images' => 'nullable|array',
                'images.*' => 'image|max:2048',
            ]);

            $product = Product::create(($validated));

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('products', 'public');

                    $product->images()->create([
                        'path' => $path,
                    ]);
                }
            }

            $product->load('images');
            return response()->json([$product, 'message' => 'Product created successfully'], 201);
        } catch (\Exception $e) {
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
            return response()->json(['message' => 'Product not found'], 404);
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
                'description' => 'required|string',
                'stock' => 'required|numeric|min:0|max:2000',
                'category' => 'required|string'
            ]);

            $product->update($validated);


            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('products', 'public');

                    $product->images()->create([
                        'path' => $path,
                    ]);
                }
            }

            if ($request->has('imagesToDelete')) {
                $imagesToDelete = json_decode($request->input('imagesToDelete'), true);
                if ($imagesToDelete && is_array($imagesToDelete)) {
                    foreach ($imagesToDelete as $imageId) {
                        $image = ProductImage::find($imageId);

                        if ($image) {
                            if (Storage::disk('public')->exists($image->path)) {
                                Storage::disk('public')->delete($image->path);
                            }

                            $image->delete();
                        }
                    }
                }

            }

            $product->load('images');
            return response()->json([$product, 'message' => 'Product updated successfully']);
        } catch (\Exception $e) {
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
            return response()->json(['message' => 'Product not found'], 404);
        }

       try {
        $product->delete();
        return response()->json(['message' => 'Product deleted successfully']);
       } catch (\Exception $e) {
        return response()->json([
            'message'=> 'Failed to delete product',
            'error'=> $e->getMessage()
        ], 500);
       }
    }
}
