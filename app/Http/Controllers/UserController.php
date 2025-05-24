<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
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

        try {
            $users = User::latest()->paginate(05);

            if ($users->isEmpty()) {
                return response()->json([
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
                    'data' => $users
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
        if ($request->user()?->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $perPage = max((int) $request->input('per_page', 50), 1);
        $columns = Schema::getColumnListing('users');
        $query = User::where('role', '!=', 'admin');

        // Text search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search, $columns) {
                foreach (['email', 'fullName', 'my_referral_code'] as $field) {
                    if (in_array($field, $columns)) {
                        $q->orWhere($field, 'like', "%{$search}%");
                    }
                }
            });
        }

        // Filter by enabled (0 or 1)
        if ($request->has('enabled') && in_array($request->enabled, ['0', '1'], true)) {
            $query->where('enabled', (int) $request->enabled);
        }

        // Filter by date (created_at)
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->input('from_date'));
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->input('to_date'));
        }

        $query->orderBy(
            'created_at',
            'desc'
        );

        return response()->json($query->paginate($perPage));
    }
    public function getUserNotification($userId)
    {
        $logs = Transaction::where('user_id', $userId)
                    ->orderBy('created_at', 'desc')
                    ->get();
    
        if ($logs->isEmpty()) {
            return response()->json(['message' => 'No notification found for this user'], 404);
        }
    
        return response()->json([
            'user_id' => $userId,
            'logs' => $logs,
        ]);
    }
}
