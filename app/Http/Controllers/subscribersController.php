<?php

namespace App\Http\Controllers;

use App\Models\subscribers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class subscribersController extends Controller
{

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $fields = $request->validate([
                'email' => 'required|email|unique:subscribers,email',
            ]);

            $subscribers = subscribers::create($fields);

            return response()->json([
                'success' => true,
                'message' => 'subscribers created successfully',
                'data' => $subscribers,
            ], 201);
        } catch (\Exception $e) {
            Log::error('subscribers creation error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create subscribers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $query = subscribers::query();

            if ($request->filled('email')) {
                $query->where('email', 'LIKE', '%' . $request->input('email') . '%');
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
            $subscribers = $query->paginate($perPage);

            if ($subscribers->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No subscribers found',
                    'data' => [],
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'subscribers retrieved successfully',
                'data' => $subscribers,
            ]);
        } catch (\Exception $e) {
            Log::error('subscribers search error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to search subscribers',
                'error' => $e->getMessage(),
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

        $subscribers = subscribers::find($id);

        if (!$subscribers) {
            return response()->json([
                'success' => false,
                'message' => 'subscribers not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'subscribers retrieved successfully',
            'data' => $subscribers,
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

        $subscribers = subscribers::find($id);

        if (!$subscribers) {
            return response()->json([
                'success' => false,
                'message' => 'subscribers not found',
            ], 404);
        }

        $subscribers->delete();

        return response()->json([
            'success' => true,
            'message' => 'subscribers deleted successfully',
        ]);
    }
}