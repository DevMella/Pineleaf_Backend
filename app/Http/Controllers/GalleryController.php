<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    // AUTHENTICATED: Upload images
    public function store(Request $request)
    {
        try {
            $request->validate([
                'type' => 'required|string',
                'images' => 'nullable|array|max:10',
                'images.*' => 'file|mimes:jpg,jpeg,png|max:5048',
            ]);

            $galleryName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $request->type ?? 'images'));
            $folderPath = "gallery/images/{$galleryName}";
            Storage::disk('public')->makeDirectory($folderPath);

            $images = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $i => $image) {
                    if ($image->isValid()) {
                        $filename = ($i + 1) . time() . '.' . $image->getClientOriginalExtension();
                        $path = $image->storeAs($folderPath, $filename, 'public');
                        $images[] = Storage::url($path);
                    }
                }
            }

            $fields['images'] = json_encode($images);

            $gallery = Gallery::create([
                'type' => $request->type,
                'images' => $images,
            ]);



            return response()->json([
                'success' => true,
                'message' => 'gallery created successfully',
                'data' => $gallery,
            ], 201);
        } catch (\Exception $e) {
            Log::error('gallery creation error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create gallery: ' . $e->getMessage()
            ], 500);
        }
    }

    // List all types
    public function types()
    {
        try {
            $types = Gallery::distinct()->pluck('type');

            return response()->json([
                'success' => true,
                'data' => $types
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve types',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // GENERAL: View all uploads
    public function index()
    {
        try {
            $gallery = Gallery::latest()->paginate(50);

            if ($gallery->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No data found',
                    'data' => [
                        'pagination' => [
                            'total' => 0,
                            'per_page' => 50,
                            'current_page' => 1,
                            'last_page' => 0
                        ],
                        'data' => []
                    ],
                ], 200);
            } else {

                return response()->json([
                    'success' => true,
                    'data' => $gallery
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve gallery',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // GENERAL: View single upload
    public function show($id)
    {
        try {
            $gallery = Gallery::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $gallery,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'gallery not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    // GENERAL: Filter by type
    public function search(Request $request)
    {
        try {
            $query = Gallery::query();

            if ($request->has('type')) {
                $query->where('type', 'LIKE', '%' . $request->input('type') . '%');
            }

            $gallery = $query->paginate(50);

            if ($gallery->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No gallery found',
                    'data' => [],
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'gallery retrieved successfully',
                'data' => $gallery,
            ]);
        } catch (\Exception $e) {
            Log::error('gallery search error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to search gallery',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // AUTHENTICATED: Delete
    public function destroy($id)
    {
        try {
            $gallery = Gallery::findOrFail($id);

            if ($gallery) {
                // delete images from storage
                foreach ($gallery->images as $path) {
                    Storage::disk('public')->delete($path);
                }

                $gallery->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'gallery deleted successfully',
                    'data' => $gallery
                ], 200);
            }
            return response()->json(['message' => 'gallery not found'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'gallery not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}