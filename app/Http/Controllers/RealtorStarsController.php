<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class RealtorStarsController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->user()?->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $perPage = max((int) $request->input('per_page', 50), 1);
        $columns = Schema::getColumnListing('users');
        $query = User::where('role', '!=', 'admin')->where('star', '!=', 0);

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
}
