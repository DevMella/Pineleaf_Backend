<?php

namespace App\Http\Controllers;

use App\Models\testimonials;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TestimonialsController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $fields = $request->validate([
                'name' => 'required|string|max:255',
                'image' => 'nullable|file|mimes:,jpg,jpeg,png|max:5048',
                'position' => 'nullable|string|max:255',
                'company' => 'nullable|string|max:255',
                'message' => 'required|string|max:2000',
                'rating' => 'required|integer|min:1|max:5',
            ]);


            $testimonialName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $request->name ?? 'testimony'));
            $folderPath = "testimonial/images/{$testimonialName}";
            Storage::disk('public')->makeDirectory($folderPath);

            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $image = $request->file('image');
                $imagePath = $image->storeAs(
                    'testimonial/images',
                    "{$testimonialName}_image_" . time() . '.' . $image->getClientOriginalExtension(),
                    'public'
                );
                $fields['image'] = Storage::url($imagePath);
            }

            $testimonial = testimonials::create($fields);

            return response()->json([
                'success' => true,
                'message' => 'testimonial created successfully',
                'data' => $testimonial,
            ], 201);
        } catch (\Exception $e) {
            Log::error('testimonial creation error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create testimonial: ' . $e->getMessage()
            ], 500);
        }
    }

    // /**
    //  * Display a listing of the resource.
    //  */
    // public function index(Request $request)
    // {
    //     $user = $request->user();
    //     if (!$user || $user->role !== 'admin') {
    //         return response()->json(['message' => 'Unauthorized'], 403);
    //     }

    //     try {
    //         $query = testimonials::query();

    //         if ($request->filled('email')) {
    //             $query->where('email', 'LIKE', '%' . $request->input('email') . '%');
    //         }

    //         // Filter by date (created_at)
    //         if ($request->has('from_date')) {
    //             $query->whereDate('created_at', '>=', $request->input('from_date'));
    //         }
    //         if ($request->has('to_date')) {
    //             $query->whereDate('created_at', '<=', $request->input('to_date'));
    //         }

    //         // Sorting and pagination
    //         $perPage = (int) $request->get('per_page', 10);
    //         $sortField = $request->get('sort_by', 'created_at');
    //         $sortDirection = strtolower($request->get('sort_direction', 'desc'));

    //         // Validate sort direction
    //         if (!in_array($sortDirection, ['asc', 'desc'])) {
    //             $sortDirection = 'desc';
    //         }

    //         $query->orderBy($sortField, $sortDirection);

    //         // Paginate results
    //         $testimonials = $query->paginate($perPage);

    //         if ($testimonials->isEmpty()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'No testimonials found',
    //                 'data' => [],
    //             ], 404);
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'testimonials retrieved successfully',
    //             'data' => $testimonials,
    //         ]);
    //     } catch (\Exception $e) {
    //         Log::error('testimonials search error', [
    //             'message' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString(),
    //         ]);
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to search testimonials',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        // $user = $request->user();
        // if (!$user || $user->role !== 'admin') {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }
        $testimonials = testimonials::find($id);

        if (!$testimonials) {
            return response()->json([
                'success' => false,
                'message' => 'testimonials not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'testimonials retrieved successfully',
            'data' => $testimonials,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        // $user = $request->user();
        // if (!$user || $user->role !== 'admin') {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        $testimonials = testimonials::find($id);

        if (!$testimonials) {
            return response()->json([
                'success' => false,
                'message' => 'testimonials not found',
            ], 404);
        }

        $testimonials->delete();

        return response()->json([
            'success' => true,
            'message' => 'testimonials deleted successfully',
        ]);
    }
}