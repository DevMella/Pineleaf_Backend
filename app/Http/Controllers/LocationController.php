<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Location::query();

            // Filter by "town"
            if ($request->has('town')) {
                $query->where('town', 'like', '%' . $request->input('town') . '%');
            }

            if ($request->has('state')) {
                $query->where('state', 'like', '%' . $request->input('state') . '%');
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
            $locations = $query->paginate($perPage);

            if ($locations->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No locations found',
                    'data' => [],
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'locations retrieved successfully',
                'data' => $locations,
            ]);
        } catch (\Exception $e) {
            Log::error('locations search error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to search locations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        $location = Location::find($id);

        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => 'location not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'location retrieved successfully',
            'data' => $location,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $location = Location::find($id);

        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => 'location not found',
            ], 404);
        }

        $location->delete();

        return response()->json([
            'success' => true,
            'message' => 'location deleted successfully',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }


        try {
            $validator = Validator::make($request->all(), [
                'town' => 'required|string|max:255|unique:locations,town',
                'state' => 'required|in:Abia,Adamawa,Akwa Ibom,Anambra,Bauchi,Bayelsa,Benue,Borno,Cross River,Delta,Ebonyi,Edo,Ekiti,Enugu,Gombe,Imo,Jigawa,Kaduna,Kano,Katsina,Kebbi,Kogi,Kwara,Lagos,Nasarawa,Niger,Ogun,Ondo,Osun,Oyo,Plateau,Rivers,Sokoto,Taraba,Yobe,Zamfara,Abuja',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $location = Location::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Location created successfully',
                'data' => $location,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Location creation error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create location',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            // Handle multipart form data for PUT/PATCH requests
            $inputData = [];

            if ($request->header('Content-Type') && strpos($request->header('Content-Type'), 'multipart/form-data') !== false) {
                // Parse multipart form data manually for PUT/PATCH requests
                $rawContent = $request->getContent();
                if (preg_match_all('/name="([^"]+)"\s*\n\s*\n([^\n-]*)/i', $rawContent, $matches)) {
                    for ($i = 0; $i < count($matches[1]); $i++) {
                        $key = $matches[1][$i];
                        $value = trim($matches[2][$i]);
                        $inputData[$key] = $value;
                    }
                }
            } else {
                // Use regular request data for JSON requests
                $inputData = $request->all();
            }

            $validator = Validator::make($inputData, [
                'town' => 'sometimes|string|max:255|unique:locations,town,' . $id,
                'state' => 'sometimes|in:Abia,Adamawa,Akwa Ibom,Anambra,Bauchi,Bayelsa,Benue,Borno,Cross River,Delta,Ebonyi,Edo,Ekiti,Enugu,Gombe,Imo,Jigawa,Kaduna,Kano,Katsina,Kebbi,Kogi,Kwara,Lagos,Nasarawa,Niger,Ogun,Ondo,Osun,Oyo,Plateau,Rivers,Sokoto,Taraba,Yobe,Zamfara,Abuja',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $location = Location::find($id);
            if (!$location) {
                return response()->json([
                    'success' => false,
                    'message' => 'Location not found',
                ], 404);
            }

            // Get only the fields we want to update
            $updateData = [];
            if (isset($inputData['town'])) {
                $updateData['town'] = $inputData['town'];
            }
            if (isset($inputData['state'])) {
                $updateData['state'] = $inputData['state'];
            }

            // Check if there's actually data to update
            if (empty($updateData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid data provided for update',
                ], 422);
            }

            // Update using Eloquent model
            $location->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Location updated successfully',
                'data' => $location->fresh(),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Location update error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update location',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}