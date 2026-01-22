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
     * PARA SA ADMIN LANG: Makikita ang lahat ng applications base sa status.
     */
    public function index(Request $request)
    {
        // 1. Siguraduhin na Admin lang ang may access
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'Forbidden: Admins only can view the list.'
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
     * PARA SA LAHAT: Kahit sinong logged-in user ay pwedeng mag-submit.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'suffix'     => 'nullable|string|max:10',
            'email' => 'required|email|max:255',
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
        ]);

        // Force set defaults para hindi ma-manipulate ng user ang status sa simula
        $validated['status'] = 'Pending';
        $validated['user_id'] = $request->user()->user_id; // Gamit ang custom user_id mo

        $application = Application::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Application submitted successfully.',
            // Hindi na natin ibabalik ang buong data para hindi makita ang status field kung ayaw mo talaga
        ], Response::HTTP_CREATED);
    }

    /**
     * GET /api/applications/{application}
     * PARA SA ADMIN LANG: Admin lang ang pwedeng tumingin ng detalye ng isang specific application.
     */
    public function show(Request $request, Application $application)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Only admins can view application details.'
            ], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => $application
        ], Response::HTTP_OK);
    }

    /**
     * PUT /api/applications/{application}
     * PARA SA ADMIN LANG: Dito mag-a-approve o disapprove si Admin.
     */
    public function update(Request $request, Application $application)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Only admins can update status.'
            ], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:Approved,Disapproved,Pending',
            'reason_for_disapproval' => 'required_if:status,Disapproved|string|nullable',
            'notes' => 'nullable|string',
        ]);

        // Auto-fill ng admin info
        $validated['reviewed_by'] = $request->user()->name;
        $validated['date_reviewed'] = now();

        $application->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Application updated successfully by Admin.',
            'data' => $application
        ], Response::HTTP_OK);
    }

    /**
     * DELETE /api/applications/{application}
     * PARA SA ADMIN LANG: Admin lang ang pwedeng mag-delete.
     */
    public function destroy(Request $request, Application $application)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.'
            ], 403);
        }

        $application->delete();
        return response()->json(['message' => 'Application deleted.'], 200);
    }
}