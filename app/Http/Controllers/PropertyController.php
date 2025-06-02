<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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
                'images.*' => 'file|mimes:jpg,jpeg,png|max:5048',
                'location' => 'required|integer|max:25',
                'landmark' => 'required',
                'size' => 'required|string|max:255',
                'land_condition' => 'required|string|max:255',
                'document_title' => 'required|string|max:255',
                'property_features' => 'required',
                'type' => 'required|in:land,house',
                'purpose' => 'required|in:residential,commercial,mixed_use',
                'price' => 'required|numeric',
                'total_units' => 'required|integer',
                'flyer' => 'nullable|file|mimes:,jpg,jpeg,png|max:5048',
            ]);

            $fields['landmark'] = json_encode(
                is_array($request->landmark)
                    ? $request->landmark
                    : json_decode(str_replace("'", '"', $request->landmark), true) ?? []
            );

            $fields['property_features'] = json_encode(
                is_array($request->property_features)
                    ? $request->property_features
                    : json_decode(str_replace("'", '"', $request->property_features), true) ?? []
            );

            $propertyName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $request->name ?? $request->estate_name ?? 'property'));
            $folderPath = "properties/images/{$propertyName}";
            Storage::disk('public')->makeDirectory($folderPath);

            $images = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $i => $image) {
                    if ($image->isValid()) {
                        $filename = ($i + 1) . '.' . $image->getClientOriginalExtension();
                        $path = $image->storeAs($folderPath, $filename, 'public');
                        $images[] = Storage::url($path);
                    }
                }
            }

            $fields['images'] = json_encode($images);

            if ($request->hasFile('flyer') && $request->file('flyer')->isValid()) {
                $flyer = $request->file('flyer');
                $flyerPath = $flyer->storeAs(
                    'properties/flyers',
                    "{$propertyName}_flyer_" . time() . '.' . $flyer->getClientOriginalExtension(),
                    'public'
                );
                $fields['flyer'] = Storage::url($flyerPath);
            }

            $property = Property::create($fields);

            return response()->json([
                'success' => true,
                'message' => 'Property created successfully',
                'data' => $property,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Property creation error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create property: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified property in storage.
     */
    public function update(Request $request, $id)
    {
        // $user = $request->user();
        // if (!$user || $user->role !== 'admin') {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        $property = Property::find($id);  
        if (!$property) {
            return response()->json(['message' => 'Property not found'], 404);
        }

        try {
            $fields = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'estate_name' => 'nullable|string|max:255',
                'description' => 'sometimes|required|string',
                'images' => 'nullable|array|max:4',
                'images.*' => 'file|mimes:jpg,jpeg,png|max:5048',
                'location' => 'sometimes|required|integer|max:25',
                'landmark' => 'sometimes|required',
                'size' => 'sometimes|required|string|max:255',
                'land_condition' => 'sometimes|required|string|max:255',
                'document_title' => 'sometimes|required|string|max:255',
                'property_features' => 'sometimes|required',
                'type' => 'sometimes|required|in:land,house',
                'purpose' => 'sometimes|required|in:residential,commercial,mixed_use',
                'price' => 'sometimes|required|numeric',
                'total_units' => 'sometimes|required|integer',
                'flyer' => 'nullable|file|mimes:jpg,jpeg,png|max:5048',
            ]);

            if ($request->has('landmark')) {
                $fields['landmark'] = json_encode(
                    is_array($request->landmark)
                        ? $request->landmark
                        : json_decode(str_replace("'", '"', $request->landmark), true) ?? []
                );
            }

            if ($request->has('property_features')) {
                $fields['property_features'] = json_encode(
                    is_array($request->property_features)
                        ? $request->property_features
                        : json_decode(str_replace("'", '"', $request->property_features), true) ?? []
                );
            }

            $propertyName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $request->name ?? $property->name ?? 'property'));
            $folderPath = "properties/images/{$propertyName}";
            Storage::disk('public')->makeDirectory($folderPath);

            if ($request->hasFile('images')) {
                $images = [];
                foreach ($request->file('images') as $i => $image) {
                    if ($image->isValid()) {
                        $filename = ($i + 1) . '.' . $image->getClientOriginalExtension();
                        $path = $image->storeAs($folderPath, $filename, 'public');
                        $images[] = Storage::url($path);
                    }
                }
                $fields['images'] = json_encode($images);
            }

            if ($request->hasFile('flyer') && $request->file('flyer')->isValid()) {
                $flyer = $request->file('flyer');
                $flyerPath = $flyer->storeAs(
                    'properties/flyers',
                    "{$propertyName}_flyer_" . time() . '.' . $flyer->getClientOriginalExtension(),
                    'public'
                );
                $fields['flyer'] = Storage::url($flyerPath);
            }

            $property->update($fields);
            $property = $property->refresh();
            $property->landmark = json_decode($property->landmark, true);
            $property->images = json_decode($property->images, true);
            $property->property_features = json_decode($property->property_features, true);
            return response()->json([
                'success' => true,
                'message' => 'Property updated successfully',
                'data' => $property,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Property update error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update property: ' . $e->getMessage()
            ], 500);
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
    public function each($id)
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
     */
    public function search(Request $request)
    {
        try {
            $query = Property::query();

            // Filter by name (checks name and estate_name)
            if ($request->filled('name')) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'LIKE', '%' . $request->input('name') . '%')
                        ->orWhere('estate_name', 'LIKE', '%' . $request->input('name') . '%');
                });
            }

            // Filter by location
            if ($request->filled('location')) {
                $query->where(function ($q) use ($request) {
                    $q->where('location', 'LIKE', '%' . $request->input('location') . '%')
                        ->orWhere('estate_name', 'LIKE', '%' . $request->input('location') . '%');
                });
            }

            // Filter by price range
            if ($request->filled('min_price') && is_numeric($request->min_price)) {
                $query->where('price', '>=', $request->min_price);
            }

            if ($request->filled('max_price') && is_numeric($request->max_price)) {
                $query->where('price', '<=', $request->max_price);
            }

            // Filter by type
            if ($request->filled('type') && in_array($request->type, ['land', 'house'])) {
                $query->where('type', $request->type);
            }

            // Filter by purpose
            if ($request->filled('purpose') && in_array($request->purpose, ['residential', 'commercial', 'mixed_use'])) {
                $query->where('purpose', $request->purpose);
            }

            // Filter by size
            if ($request->filled('size')) {
                $query->where('size', 'LIKE', '%' . $request->input('size') . '%');
            }

            // Filter by document title
            if ($request->filled('document_title')) {
                $query->where('document_title', 'LIKE', '%' . $request->input('document_title') . '%');
            }

            // Filter by availability
            if ($request->has('units') && $request->boolean('units')) {
                $query->where('total_units', '>', 0);
            }


            // Filter by date (created_at)
            if ($request->has('from_date')) {
                $query->whereDate('created_at', '>=', $request->input('from_date'));
            }
            if ($request->has('to_date')) {
                $query->whereDate('created_at', '<=', $request->input('to_date'));
            }

            // Sorting and pagination
            $perPage = (int) $request->get('per_page', 10);
            $sortField = $request->get('sort_by', 'created_at');
            $sortDirection = strtolower($request->get('sort_direction', 'desc'));

            // Validate sort direction
            if (!in_array($sortDirection, ['asc', 'desc'])) {
                $sortDirection = 'desc';
            }

            $query->orderBy($sortField, $sortDirection);

            // Paginate results
            $properties = $query->paginate($perPage);

            if ($properties->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No properties found',
                    'data' => [],
                ], 404);
            }

            // Decode JSON fields
            $properties->getCollection()->transform(function ($property) {
                $property->landmark = json_decode($property->landmark);
                $property->property_features = json_decode($property->property_features);
                $property->images = json_decode($property->images);
                return $property;
            });

            return response()->json([
                'success' => true,
                'message' => 'Properties retrieved successfully',
                'data' => $properties,
            ]);
        } catch (\Exception $e) {
            Log::error('Property search error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to search properties',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}