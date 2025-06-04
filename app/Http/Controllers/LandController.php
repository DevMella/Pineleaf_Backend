<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class LandController extends Controller
{
    public function store(Request $request)
{
    // $user = $request->user();
    // if (!$user || $user->role !== 'admin') {
    //     return response()->json(['message' => 'Unauthorized'], 403);
    // }

    try {
        $fields = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB max
        ]);

        // Create a sanitized folder name
        $folderName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $fields['name']));
        $folderPath = "documents/{$folderName}";
        Storage::disk('public')->makeDirectory($folderPath);

        // Save document
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $filename = "{$folderName}_" . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs($folderPath, $filename, 'public');
            $fields['document_path'] = Storage::url($path);
        }

        // Save to database
        $doc = Document::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'document_path' => $fields['document_path'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Document uploaded successfully',
            'data' => $doc
        ], 201);

    } catch (\Exception $e) {
        Log::error('Document upload failed', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to upload document: ' . $e->getMessage()
        ], 500);
    }
}

public function index(Request $request)
{
    // $user = $request->user();
    // if (!$user || $user->role !== 'admin') {
    //     return response()->json(['message' => 'Unauthorized'], 403);
    // }

    try {
        $query = Document::query();
        $columns = Schema::getColumnListing('documents');

        // Text search (searches in 'title' and 'description', modify as needed)
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $searchTerm = $request->input('search');
                $q->where('title', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('description', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        // Filter by uploader (user_id)
        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Filter by department
        if ($request->has('department')) {
            $query->where('department', 'LIKE', '%' . $request->input('department') . '%');
        }

        // Filter by date
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

        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $query->orderBy($sortField, $sortDirection);

        $documents = $query->paginate($perPage);

        if ($documents->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No documents found',
                'data' => [],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Documents retrieved successfully',
            'data' => $documents,
        ]);

    } catch (\Exception $e) {
        Log::error('Document retrieval error', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve documents',
            'error' => $e->getMessage(),
        ], 500);
    }
}

}
