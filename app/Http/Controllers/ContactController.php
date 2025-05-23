<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ContactController extends Controller
{
    // Public Create endpoint
    public function create(Request $request)
    {
        try {
            $validated = $request->validate([
                'firstname' => 'required|string',
                'lastname' => 'required|string',
                'phone' => 'required|string|unique:contacts,phone',
                'email' => 'required|email|unique:contacts,email',
                'subject' => 'required|string',
            ]);

            $contact = Contact::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'contact created successfully',
                'data' => $contact,
            ], 201);
        } catch (\Exception $e) {
            Log::error('contact creation error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create contact: ' . $e->getMessage()
            ], 500);
        }
    }

    // Authenticated: View All
    public function index(Request $request)
    {
        try {
            $query = Contact::query();
            $columns = Schema::getColumnListing('contact');

            // Text search
            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('email', 'LIKE', '%' . $request->input('search') . '%')
                        ->orWhere('firstname', 'LIKE', '%' . $request->input('search') . '%')
                        ->orWhere('lastname', 'LIKE', '%' . $request->input('search') . '%');
                });
            }


            // Filter by "phone"
            if ($request->has('phone')) {
                $query->where('phone', 'like', '%' . $request->input('phone') . '%');
            }
            // Filter by "subject"
            if ($request->has('subject')) {
                $query->where('subject', 'like', '%' . $request->input('subject') . '%');
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
            $contact = $query->paginate($perPage);

            if ($contact->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No contact found',
                    'data' => [],
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'contact retrieved successfully',
                'data' => $contact,
            ]);
        } catch (\Exception $e) {
            Log::error('contact search error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to search contact',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Authenticated: View One
    public function show(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $contact = Contact::find($id);

        if (!$contact) {
            return response()->json([
                'success' => false,
                'message' => 'contact not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'contact retrieved successfully',
            'data' => $contact,
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

        $contact = Contact::find($id);

        if (!$contact) {
            return response()->json([
                'success' => false,
                'message' => 'contact not found',
            ], 404);
        }

        $contact->delete();

        return response()->json([
            'success' => true,
            'message' => 'contact deleted successfully',
        ]);
    }
}