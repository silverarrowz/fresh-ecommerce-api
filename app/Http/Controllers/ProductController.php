<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use GuzzleHttp\Client;

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
        return response()->json(
            Product::latest()->paginate($perPage)
        );
    }

    public function getLatest()
    {
        return response()->json(Product::latest()->take(10)->get());
    }

    public function search(Request $request)
    {
        $query = $request->input('query');
        $limit = $request->input('limit', 10);

        if (!$query) {
            return response()->json([], 200);
        }

        $products = Product::where('title', 'ILIKE', "%{$query}%")
            ->orWhereHas('category', function ($q) use ($query) {
                $q->where('name', 'ILIKE', "%{$query}%");
            })
            ->limit($limit)
            ->get();

        return response()->json($products);
    }

    public function getByCategory(string $slug, Request $request) {
        $category = Category::where('slug', $slug)->first();

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $limit = $request->input('limit', 10);
        $products = Product::where('category_id', $category->id)
        ->limit($limit)
        ->get();

        return response()->json($products);
    }


    public function getByTag(Request $request)
    {
        $tag = $request->input('tag');
        $products = Product::whereJsonContains('tags', $tag)->get();

        return response()->json($products);
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
                'category_id' => 'required|exists:categories,id',
                'images' => 'nullable|array',
                'images.*' => 'image|max:2048',
            ]);

            $product = Product::create($validated);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {

                    $filename = Str::uuid() . '.webp';

                    $webpImage = Image::read($image)->scale(700)->toWebp(90);

                    try {
                        $client = new Client();
                        $supabaseUrl = config('filesystems.disks.supabase.url');
                        $bucket = config('filesystems.disks.supabase.bucket');
                        $apiKey = config('filesystems.disks.supabase.key');

                        Log::info('Supabase configuration:', [
                            'url' => $supabaseUrl,
                            'bucket' => $bucket,
                            'has_api_key' => !empty($apiKey),
                        ]);

                        $url = "{$supabaseUrl}/storage/v1/object/{$bucket}/products/{$filename}";
                        Log::info('Upload URL:', ['url' => $url]);

                        $response = $client->request('POST', $url, [
                            'headers' => [
                                'apikey' => $apiKey,
                                'Authorization' => 'Bearer ' . $apiKey,
                                'Content-Type' => 'image/webp',
                            ],
                            'body' => (string) $webpImage,
                        ]);

                        $result = $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;

                        if (!$result) {
                            throw new \Exception('Failed to upload file to Supabase');
                        }

                        $publicUrl = "{$supabaseUrl}/storage/v1/object/public/{$bucket}/products/{$filename}";
                        Log::info('Public URL:', ['url' => $publicUrl]);

                        $product->images()->create([
                            'path' => "products/{$filename}",
                            'url' => $publicUrl,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Upload operation failed:', [
                            'error' => $e->getMessage(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        throw $e;
                    }
                }
            } else {
                Log::info('No images were uploaded with the request');
            }

            $product->load(['images', 'category']);

            return response()->json([
                'product' => $product,
                'message' => 'Product created successfully'
            ], 201);
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
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }

        return response()->json($product);
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
                'category_id' => 'required|exists:categories,id',
                'images' => 'nullable|array',
                'images.*' => 'image|max:2048',
                'imagesToDelete' => 'nullable|json'
            ]);

            $product->update($validated);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {

                    $filename = Str::uuid() . '.webp';
                    $webpImage = Image::read($image)->scale(700)->toWebp(90);

                    try {
                        $client = new Client();
                        $supabaseUrl = config('filesystems.disks.supabase.url');
                        $bucket = config('filesystems.disks.supabase.bucket');
                        $apiKey = config('filesystems.disks.supabase.key');

                        $url = "{$supabaseUrl}/storage/v1/object/{$bucket}/products/{$filename}";
                        $response = $client->request('POST', $url, [
                            'headers' => [
                                'apikey' => $apiKey,
                                'Authorization' => 'Bearer ' . $apiKey,
                                'Content-Type' => 'image/webp',
                            ],
                            'body' => (string) $webpImage,
                        ]);

                        $result = $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;

                        if (!$result) {
                            throw new \Exception('Failed to directly upload file to Supabase');
                        }

                        $publicUrl = "{$supabaseUrl}/storage/v1/object/public/{$bucket}/products/{$filename}";
                        $product->images()->create([
                            'path' => "products/{$filename}",
                            'url' => $publicUrl,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Upload operation failed:', [
                            'error' => $e->getMessage(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        throw $e;
                    }
                }
            } else {
                Log::info('No images were uploaded with the request');
            }

            if ($request->has('imagesToDelete')) {
                $imagesToDelete = json_decode($request->input('imagesToDelete'), true);
                if ($imagesToDelete && is_array($imagesToDelete)) {
                    foreach ($imagesToDelete as $imageId) {
                        $image = ProductImage::find($imageId);

                        if ($image) {
                            try {
                                if (Storage::disk('supabase')->exists($image->path)) {
                                    Storage::disk('supabase')->delete($image->path);
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

            $product->load(['images', 'category']);
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
                if (Storage::disk('supabase')->exists($image->path)) {
                    Storage::disk('supabase')->delete($image->path);
                }
            }

            $product->delete();

            return response()->json([
                'message' => 'Product and associated images deleted successfully'
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
