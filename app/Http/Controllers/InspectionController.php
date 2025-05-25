<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InspectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $query = Inspection::query();

        // Search by fullname, email, or phone
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('fullname', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('phone', 'like', "%$search%");
            });
        }

        // Filter by any column (dynamically)
        $filterable = ['status', 'property_id', 'inspection_date'];

        foreach ($filterable as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        // Optional: date range filtering
        if ($request->filled('date_from')) {
            $query->whereDate('inspection_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('inspection_date', '<=', $request->input('date_to'));
        }

        // Filter by date (created_at)
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->input('from_date'));
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->input('to_date'));
        }

        // Order by latest
        $inspections = $query->latest()->paginate(20);

        return response()->json($inspections);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'fullname' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'required|string|max:20',
                'property_id' => 'required|exists:properties,id',
                'inspection_date' => 'required|date',
                'inspection_time' => 'required|date_format:H:i',
                'notes' => 'nullable|string',
                'no_attendees' => 'required|integer|min:1|max:10', // Assuming a maximum of 10 attendees
            ]);

            $inspection = Inspection::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Inspection created successfully',
                'data' => $inspection
            ], 201);
        } catch (\Exception $e) {
            Log::error('Inspection creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create inspection',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $inspection = Inspection::find($id);

            if (!$inspection) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inspection not found'
                ], 404);
            }
            return response()->json([
                'success' => true,
                'message' => 'Inspection retrieved successfully',
                'data' => $inspection
            ], 200);
        } catch (\Exception $e) {
            Log::error('Inspection retrieval failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve inspection',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * complete the specified resource in storage.
     */
    public function complete(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $inspection = Inspection::find($id);

            if (!$inspection) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inspection not found'
                ], 404);
            }

            $inspection->status = 'confirmed';
            $inspection->save();

            return response()->json([
                'success' => true,
                'message' => 'Inspection completed successfully',
                'data' => $inspection
            ], 200);
        } catch (\Exception $e) {
            Log::error('Inspection completion failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete inspection',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Cancel the specified resource in storage.
     */
    public function cancelled(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $inspection = Inspection::find($id);

            if (!$inspection) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inspection not found'
                ], 404);
            }

            $inspection->status = 'cancelled';
            $inspection->save();

            return response()->json([
                'success' => true,
                'message' => 'Inspection cancelled successfully',
                'data' => $inspection
            ], 200);
        } catch (\Exception $e) {
            Log::error('Inspection cancelled failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel inspection',
                'error' => $e->getMessage()
            ], 500);
        }
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

        try {
            $inspection = Inspection::find($id);

            if (!$inspection) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inspection not found'
                ], 404);
            }

            $inspection->delete();

            return response()->json([
                'success' => true,
                'message' => 'Inspection deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Inspection deletion failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete inspection',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}