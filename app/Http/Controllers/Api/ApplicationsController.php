<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ApplicationsController extends Controller
{
    /**
     * GET /api/applications
     * Restricted: Admin only (Dapat gamitan ng middleware sa routes)
     */
    public function index(Request $request)
    {
        // Dahil inalis natin ang global middleware, kailangan i-check kung auth ang user dito
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'Forbidden: Admins only.'
            ], 403);
        }

        $status = $request->query('status', 'Pending');

        $applications = Application::where('status', $status)
            ->orderBy('date_submitted', 'desc')
            ->paginate(15);

        return response()->json([
            'status' => true,
            'viewing_status' => $status,
            'count' => $applications->total(),
            'data' => $applications
        ]);
    }

    /**
     * POST /api/applications
     * PUBLIC: Kahit sino pwedeng mag-register (No Bearer Token Required)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'suffix'     => 'nullable|string|max:10',
            'email' => 'required|email|max:255',
            'contact_number' => 'required|string|max:20',
            'citizenship'=> 'required|string|max:255',
            'house_no'=> 'required|string|max:255',
            'street' => 'required|string|max:255',
            'barangay' => 'required|string|max:255',
            'city_municipality' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'age' => 'required|integer',
            'gender' => 'required|string',
            'civil_status' => 'required|string',
            'birthdate' => 'required|date',
            'birthplace' => 'required|string',
            'living_arrangement' => 'required|string',
            'is_pensioner' => 'required|boolean',
            'pension_source' => 'nullable|string',
            'pension_amount' => 'nullable|string',
            'has_illness' => 'required|boolean',
            'illness_details' => 'nullable|string',
            'document_url' => 'required|string',
            'application_type' => 'required|string',
            'user_id' => 'nullable|integer', // Optional na lang ito dahil public registration ito
        ]);

        // Default settings para sa bagong registration
        $validated['status'] = 'Pending';
        $validated['date_submitted'] = now();

        $application = Application::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Application submitted successfully.',
        ], Response::HTTP_CREATED);
    }

    /**
     * GET /api/applications/{application}
     * Restricted: Admin only
     */
    public function show(Request $request, Application $application)
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
        }

        return response()->json(['status' => 'success', 'data' => $application], Response::HTTP_OK);
    }

    /**
     * PUT /api/applications/{application}
     * Restricted: Admin only
     */
    public function update(Request $request, Application $application)
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:Approved,Disapproved,Pending',
            'reason_for_disapproval' => 'required_if:status,Disapproved|string|nullable',
            'notes' => 'nullable|string',
        ]);

        $validated['reviewed_by'] = $request->user()->name;
        $validated['date_reviewed'] = now();

        $application->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Application updated successfully.',
            'data' => $application
        ], Response::HTTP_OK);
    }

    /**
     * DELETE /api/applications/{application}
     * Restricted: Admin only
     */
    public function destroy(Request $request, Application $application)
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
        }

        $application->delete();
        return response()->json(['message' => 'Application deleted.'], 200);
    }
}