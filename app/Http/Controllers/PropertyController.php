<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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
            \Log::error('Property search error', [
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

    
    /**
     * Update the specified property in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $property = Property::find($id);
            if (!$property) {
                return response()->json([
                    'success' => false,
                    'message' => 'Property not found',
                ], 404);
            }
            Log::info('Incoming request data:', $request->all());

            // First validate non-file fields
            $validatedData = $request->validate([
                'name' => 'sometimes|string|max:255',
                'estate_name' => 'nullable|string|max:255',
                'description' => 'sometimes|string',
                'location' => 'sometimes|string|max:255',
                'landmark' => 'sometimes|string', // Changed from sometimes to sometimes|string
                'size' => 'sometimes|string|max:255',
                'land_condition' => 'sometimes|string|max:255',
                'document_title' => 'sometimes|string|max:255',
                'property_features' => 'sometimes|string', // Changed from sometimes to sometimes|string
                'type' => 'sometimes|in:land,house',
                'purpose' => 'sometimes|in:residential,commercial,mixed_use',
                'price' => 'sometimes|numeric',
                'total_units' => 'sometimes|integer',
                'images' => 'sometimes|array|max:4',
                'images.*' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
                'flyer' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            ]);

            Log::info('Validated data:', $validatedData);

            // Handle JSON fields
            foreach (['landmark', 'property_features'] as $field) {
                if ($request->has($field)) {
                    $value = $request->input($field);
                    if (is_string($value)) {
                        $decoded = json_decode($value, true);
                        $validatedData[$field] = $decoded !== null ? json_encode($decoded) : json_encode([]);
                    } elseif (is_array($value)) {
                        $validatedData[$field] = json_encode($value);
                    }
                }
            }

            // Handle image uploads
            if ($request->hasFile('images')) {
                $propertyName = strtolower(preg_replace(
                    '/[^a-zA-Z0-9]/',
                    '_',
                    $property->name ?? $request->input('name') ?? $request->input('estate_name') ?? 'property'
                ));
                $folderPath = "properties/images/{$propertyName}";

                // Delete old images if needed
                // Storage::disk('public')->deleteDirectory($folderPath);

                Storage::disk('public')->makeDirectory($folderPath);
                $images = [];

                foreach ($request->file('images') as $i => $image) {
                    if ($image->isValid()) {
                        $path = $image->storeAs(
                            $folderPath,
                            ($i + 1) . '.' . $image->getClientOriginalExtension(),
                            'public'
                        );
                        $images[] = Storage::url($path);
                    }
                }

                $validatedData['images'] = json_encode($images);
            }

            // Handle flyer upload
            if ($request->hasFile('flyer') && $request->file('flyer')->isValid()) {
                $propertyName = strtolower(preg_replace(
                    '/[^a-zA-Z0-9]/',
                    '_',
                    $property->name ?? $request->input('name') ?? $request->input('estate_name') ?? 'property'
                ));

                $flyer = $request->file('flyer');
                $path = $flyer->storeAs(
                    'properties/flyers',
                    "{$propertyName}_flyer_" . time() . '.' . $flyer->getClientOriginalExtension(),
                    'public'
                );
                $validatedData['flyer'] = Storage::url($path);
            }

            $property->update(array_filter($validatedData));

            Log::info('Updated property:', $property->fresh()->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Property updated successfully',
                'data' => $property->fresh(),
            ]);
        } catch (\Exception $e) {
            Log::error('Property update error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update property',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
