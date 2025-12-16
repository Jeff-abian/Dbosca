<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InstitutionController extends Controller
{
    /**
     * GET /api/institutions
     * Display a listing of the resource.
     * Fetches all institutions (excluding soft-deleted ones by default).
     */
    public function index()
    {
        // Fetch all active institutions
        $institutions = Institution::all();

        return response()->json([
            'status' => 'success',
            'data' => $institutions
        ], Response::HTTP_OK);
    }

    /**
     * POST /api/institutions
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Validate the incoming request data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
        ]);

        // 2. Create the institution record
        $institution = Institution::create($validated);

        // 3. Return the newly created resource with a 201 Created status
        return response()->json([
            'status' => 'success',
            'message' => 'Institution created successfully.',
            'data' => $institution
        ], Response::HTTP_CREATED);
    }

    /**
     * GET /api/institutions/{institution}
     * Display the specified resource.
     */
    public function show(Institution $institution)
    {
        // Laravel's Route Model Binding automatically fetches the institution by ID.
        // If the institution is soft-deleted, it will return a 404.
        return response()->json([
            'status' => 'success',
            'data' => $institution
        ], Response::HTTP_OK);
    }

    /**
     * PUT/PATCH /api/institutions/{institution}
     * Update the specified resource in storage.
     */
    public function update(Request $request, Institution $institution)
    {
        // 1. Validate only the fields that are present in the request
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:500',
        ]);

        // 2. Update the model instance
        $institution->update($validated);

        // 3. Return the updated resource
        return response()->json([
            'status' => 'success',
            'message' => 'Institution updated successfully.',
            'data' => $institution
        ], Response::HTTP_OK);
    }

    /**
     * DELETE /api/institutions/{institution}
     * Remove the specified resource from storage (Soft Delete).
     */
    public function destroy(Institution $institution)
    {
        // The SoftDeletes trait ensures this runs an UPDATE query, setting 'deleted_at'
        $institution->delete();

        // Standard for successful DELETE requests is 204 No Content
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}