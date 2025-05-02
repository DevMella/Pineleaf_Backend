<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class UserController extends Controller
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

        // Get pagination parameters from the request
        $perPage = $request->input('per_page', 50);

        // Apply additional filters if needed
        $query = User::where('role', '!=', 'admin');

        // Apply sorting if requested
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Execute the paginated query
        $users = $query->paginate($perPage);

        return response()->json($users);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $admin = $request->user();
        if (!$admin || $admin->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $user = User::find($id);
        if ($user) {
            $user->delete();
            return response()->json(['message' => 'User deleted successfully', 'user' => $user], 200);
        }
        return response()->json(['message' => 'User not found'], 404);
    }
    /**
     * Display a listing of the resource.
     */
    public function search(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Get pagination parameters from the request
        $perPage = $request->input('per_page', 50);
        $page = $request->input('page', 1);

        // Apply additional filters if needed
        $query = User::where('role', '!=', 'admin');

        // First, let's determine what columns actually exist in our users table
        $columns = \Schema::getColumnListing('users');

        // You can add search functionality
        if ($request->has('search') && !empty($request->input('search'))) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search, $columns) {
                // Only add these conditions if the columns exist
                if (in_array('email', $columns)) {
                    $q->orWhere('email', 'like', "%{$search}%");
                }

                // Check for other possible name columns
                if (in_array('first_name', $columns)) {
                    $q->orWhere('first_name', 'like', "%{$search}%");
                }

                if (in_array('last_name', $columns)) {
                    $q->orWhere('last_name', 'like', "%{$search}%");
                }

                if (in_array('name', $columns)) {
                    $q->orWhere('name', 'like', "%{$search}%");
                }

                if (in_array('username', $columns)) {
                    $q->orWhere('username', 'like', "%{$search}%");
                }

                // Include user_id, id, or any other potentially useful columns
                if (in_array('user_id', $columns)) {
                    // Only search by ID if the search term is numeric
                    if (is_numeric($search)) {
                        $q->orWhere('user_id', $search);
                    }
                }

                if (in_array('id', $columns)) {
                    // Only search by ID if the search term is numeric
                    if (is_numeric($search)) {
                        $q->orWhere('id', $search);
                    }
                }
            });
        }

        // Apply sorting if requested - only allow sorting on columns that exist
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = in_array(strtolower($request->input('sort_dir', 'desc')), ['asc', 'desc'])
                  ? strtolower($request->input('sort_dir', 'desc'))
                  : 'desc';

        // Use the requested sort column only if it exists in the database
        if (in_array($sortBy, $columns)) {
            $query->orderBy($sortBy, $sortDir);
        } elseif (in_array('created_at', $columns)) {
            $query->orderBy('created_at', 'desc'); // Default fallback
        } elseif (in_array('id', $columns)) {
            $query->orderBy('id', 'desc'); // Secondary fallback
        }

        // Execute the paginated query
        $users = $query->paginate($perPage);

        // Check if there are no results and return a custom message
        if ($users->isEmpty()) {
            return response()->json([
                'message' => 'No data found',
                'data' => [],
                'pagination' => [
                    'total' => 0,
                    'per_page' => (int)$perPage,
                    'current_page' => (int)$page,
                    'last_page' => 0
                ]
            ], 200);
        }

        return response()->json($users);
    }
}
