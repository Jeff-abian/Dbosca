<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\User;
use App\Models\Masterlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Exception;

class ApplicationsController extends Controller
{
    /**
     * GET /api/applications
     * Restricted: Admin and Super Admin only
     */
    public function index(Request $request)
    {
        $user = $request->user()->load('roleRelation');
        $roleName = strtolower(optional($user->roleRelation)->name);
        $allowedRoles = ['admin', 'super admin'];

        if (!$user || !in_array($roleName, $allowedRoles)) {
            return response()->json([
                'status' => false,
                'message' => 'Forbidden: Only Admins and Super Admins can view this data.',
            ], 403);
        }

        $status = $request->query('status', 'All');
        $query = Application::query();

        if ($status !== 'All') {
            $query->where('status', $status);
        }

        $applications = $query->orderBy('date_submitted', 'desc')->paginate(15);

        return response()->json([
            'status' => true,
            'viewing_status' => $status,
            'data' => $applications
        ]);
    }

    /**
     * POST /api/applications
     * PUBLIC: Submission of application
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'last_name'         => 'required|string|max:255',
            'first_name'        => 'required|string|max:255',
            'middle_name'       => 'nullable|string|max:255',
            'suffix'            => 'nullable|string|max:10',
            'email'             => 'required|email|max:255',
            'contact_number'    => 'required|string|max:20',
            'citizenship'       => 'required|string|max:255',
            'house_no'          => 'required|string|max:255',
            'street'            => 'required|string|max:255',
            'barangay'          => 'required|string|max:255',
            'city_municipality' => 'required|string|max:255',
            'province'          => 'required|string|max:255',
            'district'          => 'required|string|max:255',
            'age'               => 'required|integer',
            'gender'            => 'required|string',
            'civil_status'      => 'required|string',
            'birthdate'         => 'required|date',
            'birthplace'        => 'required|string',
            'living_arrangement'=> 'required|string',
            'is_pensioner'      => 'required|boolean',
            'pension_source'    => 'nullable|string',
            'pension_amount'    => 'nullable|string',
            'has_illness'       => 'required|boolean',
            'illness_details'   => 'nullable|string',
            'document_url'      => 'required|string',
            'application_type'  => 'required|string',
            'document_path'  => 'required|file|mimes:pdf,jpg,png|max:5120', // Validation para sa physical file
        ]);

        // Sa iyong store method
if ($request->hasFile('document_path')) {
    // I-save ang file sa storage/app/public/attachments
    $path = $request->file('document_path')->store('attachments', 'public');
    
    // Ang 'path' ay magiging: "attachments/filename.pdf"
    $validated['document_path'] = $path;
}

        $validated['status'] = 'Pending';
        $validated['date_submitted'] = now();

        Application::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Application submitted successfully.',
        ], 201);
    }

    /**
     * PUT /api/applications/{application}
     * Admin Action: Approve/Disapprove Application
     */
    public function update(Request $request, Application $application)
    {
        $user = $request->user()->load('roleRelation');
        $roleName = strtolower(optional($user->roleRelation)->name);
        $authorizedRoles = ['admin', 'super admin'];

        if (!in_array($roleName, $authorizedRoles)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Access denied.'
            ], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,disapproved,Pending',
            'reason_for_disapproval' => 'required_if:status,disapproved|string|nullable',
        ]);

        try {
            DB::transaction(function () use ($request, $application, $validated) {
                
                // 1. I-update ang Application status
                $application->update([
                    'status' => $validated['status'],
                    'reviewed_by' => $request->user()->name,
                    'date_reviewed' => now(),
                    'reason_for_disapproval' => $validated['reason_for_disapproval'] ?? null,
                ]);

                // 2. Logic kapag APPROVED
                if (strtolower($validated['status']) === 'approved') {
                    
                    // Check kung may existing user na base sa email
                    $existingUser = User::where('email', $application->email)->first();
                    $targetUserId = null;

                    if (!$existingUser) {
                        // Gawa ng bagong User account
                        $tempPassword = 'User' . now()->year . '!'; 
                        $username = Str::slug($application->first_name . $application->last_name, '.') . '.' . rand(100, 999);

                        $newUser = User::create([
                            'name'     => $application->first_name . ' ' . $application->last_name,
                            'email'    => $application->email,
                            'username' => $username,
                            'password' => Hash::make($tempPassword),
                            'role'     => 3, // Citizen Role
                        ]);
                        $targetUserId = $newUser->id;
                    } else {
                        $targetUserId = $existingUser->id;
                    }

                    // 3. Generate Unique SCID Number
                    $scidNumber = null;
                    $isUnique = false;
                    while (!$isUnique) {
                        $scidNumber = now()->year . '-' . rand(10000, 99999);
                        if (!Masterlist::where('scid_number', $scidNumber)->exists()) {
                            $isUnique = true;
                        }
                    }

                    // 4. Create Masterlist Entry
                    Masterlist::create([
                        'user_id'           => $targetUserId,
                        'id_status'         => 'pending', 
                        'scid_number'       => $scidNumber,
                        'first_name'        => $application->first_name,
                        'age'               => $application->age,
                        'last_name'         => $application->last_name,
                        'middle_name'       => $application->middle_name,
                        'email'             => $application->email,
                        'contact_number'    => $application->contact_number, // <--- ADDED
                        'birthdate'         => $application->birthdate,
                        'birthplace'        => $application->birthplace,     // <--- ADDED
                        'gender'            => $application->gender,
                        'civil_status'      => $application->civil_status,
                        'citizenship'       => $application->citizenship,
                        'house_no'          => $application->house_no,       // <--- ADDED
                        'street'            => $application->street,         // <--- ADDED
                        'barangay'          => $application->barangay,
                        'city_municipality' => $application->city_municipality,
                        'province'          => $application->province,
                        'district'          => $application->district,
                        'status'            => 'Active',
                        'date_submitted'    => $application->date_submitted ?? now(),
                        'document_path' => $application->document_path, // Dito lilipat ang reference ng file
                    ]);
                }
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Application updated. If approved, data is now in Masterlist.'
            ]);

        } catch (Exception $e) {
            return response()->json(['error' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/applications/{application}
     */
    public function destroy(Request $request, Application $application)
    {
        $user = $request->user()->load('roleRelation');
        $roleName = strtolower(optional($user->roleRelation)->name);

        if (!in_array($roleName, ['admin', 'super admin'])) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
        }

        $application->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Application record has been successfully deleted.'
        ]);
    }
}