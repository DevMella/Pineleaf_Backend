<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class PropertyController extends Controller
{
    /**
     * Display a listing of the properties.
     */
    public function index()
    {
        try {
            $properties = Property::paginate(50);

            if ($properties->isEmpty()) {
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
                    'data' => $properties
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve properties',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * show the newest property
     */
    public function latest(Request $request)
    {
        try {
            $properties = Property::latest()->paginate(10);


            if ($properties->isEmpty()) {
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
                    'data' => $properties
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created property in storage.
     */
    public function create(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $fields = $request->validate([
                'name' => 'required|string|max:255',
                'estate_name' => 'nullable|string|max:255',
                'description' => 'required|string',
                'images' => 'nullable|array|max:4',
                'images.*' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
                'location' => 'required|string|max:255',
                'landmark' => 'required',
                'size' => 'required|string|max:255',
                'land_condition' => 'required|string|max:255',
                'document_title' => 'required|string|max:255',
                'property_features' => 'required',
                'type' => 'required|in:land,house',
                'purpose' => 'required|in:residential,commercial,mixed_use',
                'price' => 'required|numeric',
                'total_units' => 'required|integer',
                'flyer' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            ]);

            $fields['landmark'] = json_encode(is_array($request->landmark) ? $request->landmark : json_decode(str_replace("'", '"', $request->landmark), true) ?? []);
            $fields['property_features'] = json_encode(is_array($request->property_features) ? $request->property_features : json_decode(str_replace("'", '"', $request->property_features), true) ?? []);

            $propertyName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $request->name ?? $request->estate_name ?? 'property'));
            $folderPath = "properties/images/{$propertyName}";
            Storage::disk('public')->makeDirectory($folderPath);

            $images = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $i => $image) {
                    if ($image->isValid()) {
                        $path = $image->storeAs($folderPath, ($i + 1) . '.' . $image->getClientOriginalExtension(), 'public');
                        $images[] = Storage::url($path);
                    }
                }
            } elseif (is_string($request->input('images'))) {
                $decoded = json_decode($request->input('images'), true);
                $images = json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? array_slice($decoded, 0, 4) : [];
            }
            $fields['images'] = json_encode($images);

            if ($request->hasFile('flyer') && $request->file('flyer')->isValid()) {
                $flyer = $request->file('flyer');
                $path = $flyer->storeAs('properties/flyers', "{$propertyName}_flyer_" . time() . '.' . $flyer->getClientOriginalExtension(), 'public');
                $fields['flyer'] = Storage::url($path);
            }

            $property = Property::create($fields);

            return response()->json([
                'success' => true,
                'message' => 'Property created successfully',
                'data' => $property,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Property creation error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Failed to create property: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified property.
     */
    public function show($id)
    {
        try {
            $property = Property::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $property,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Property not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Delete the specified property.
     */
    public function destroy(Request $request, string $id)
    {
        $admin = $request->user();
        if (!$admin || $admin->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $property = Property::findOrFail($id);

            if ($property) {
                $property->delete();
                return response()->json([
                    'success' => true,
                    'message' => 'property deleted successfully',
                    'property' => $property
                ], 200);
            }
            return response()->json(['message' => 'property not found'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Property not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Search for properties based on various filters
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        
    }
}