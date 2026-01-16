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
     */
 public function index(Request $request)
{
    // 1. Siguraduhin na Admin lang ang may access
    if ($request->user()->role !== 'admin') {
        return response()->json([
            'status' => false,
            'message' => 'Forbidden.'
        ], 403);
    }

    // 2. Kunin ang 'status' mula sa URL (halimbawa: ?status=Approved)
    // Default nito ay 'Pending' kung walang nilagay
    $status = $request->query('status', 'Pending');

    // 3. I-filter ang applications base sa status
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
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'citizen_id' => 'required|integer',

            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'suffix' => 'nullable|string|max:50',

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

            // Pension
            'is_pensioner' => 'required|boolean',
            'pension_source' => 'nullable|string',
            'pension_amount' => 'nullable|string',

            // Illness
            'has_illness' => 'required|boolean',
            'illness_details' => 'nullable|string',

            // Documents
            'document_url' => 'required|string',

            // Application info
            'application_type' => 'required|string',
            'status' => 'required|string',
        ]);

        // Idagdag ang user_id galing sa token
        $validated['user_id'] = auth()->id();

        $application = Application::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Application submitted successfully.',
            'data' => $application
        ], Response::HTTP_CREATED);
    }

    /**
     * GET /api/applications/{application}
     */
    public function show(Application $application)
{
    // Check kung ang application ay pagmamay-ari ng naka-login na user
    if ($application->user_id !== auth()->id()) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized access.'
        ], Response::HTTP_FORBIDDEN);
    }

    return response()->json([
        'status' => 'success',
        'data' => $application
    ], Response::HTTP_OK);
}

    /**
     * PUT/PATCH /api/applications/{application}
     */
    public function update(Request $request, Application $application)
    {
        $validated = $request->validate([
            'last_name' => 'sometimes|string|max:255',
            'status' => 'sometimes|string',
            'reason_for_disapproval' => 'nullable|string',
            'date_reviewed' => 'nullable|date',
            'reviewed_by' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $application->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Application updated successfully.',
            'data' => $application
        ], Response::HTTP_OK);
    }

    /**
     * DELETE /api/applications/{application}
     */
    public function destroy(Application $application)
    {
        $application->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
