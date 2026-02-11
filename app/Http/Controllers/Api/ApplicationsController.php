<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\User;
use App\Models\Masterlist;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
        
        ]);

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
    // 1. KILALANIN ANG USER AT ROLE
    $user = $request->user()->load('roleRelation');
    $roleName = strtolower(optional($user->roleRelation)->name);

    // 2. HIGPITAN ANG ACCESS: Admin at Super Admin lang ang pwede
    $authorizedRoles = ['admin', 'super admin'];
    if (!in_array($roleName, $authorizedRoles)) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized: Only Admins or Super Admins can update application status.'
        ], 403);
    }

    // 3. VALIDATION (Siguraduhin ang case-sensitivity ng 'approved')
    $validated = $request->validate([
        'status' => 'required|in:approved,disapproved,Pending',
        'reason_for_disapproval' => 'required_if:status,disapproved|string|nullable',
    ]);

    try {
        // 4. TRANSACTION
        DB::transaction(function () use ($request, $application, $validated) {
            
            // I-update muna ang status ng original application record
            $application->update([
                'status' => $validated['status'],
                'reviewed_by' => $request->user()->name,
                'date_reviewed' => now(),
                'reason_for_disapproval' => $validated['reason_for_disapproval'] ?? null,
            ]);

            // DITO DAPAT ANG IF CONDITION PARA SA APPROVED
            if ($validated['status'] === 'approved') {
                
                // Check kung may existing user na base sa email
                $existingUser = User::where('email', $application->email)->first();
                
                if (!$existingUser) {
                    // --- STEP A: GENERATE UNIQUE SCID_NUMBER ---
                    $scidNumber = null;
                    $isUnique = false;

                    while (!$isUnique) {
                        $scidNumber = now()->year . '-' . rand(10000, 99999);
                        $check = Masterlist::where('scid_number', $scidNumber)->exists();
                        if (!$check) {
                            $isUnique = true;
                        }
                    }

                    // --- STEP B: CREATE USER ACCOUNT ---
                    $tempPassword = 'User' . now()->year . '!'; 
                    $username = Str::slug($application->first_name . $application->last_name, '.') . '.' . rand(100, 999);

                    $newUser = User::create([
                        'name'     => $application->first_name . ' ' . $application->last_name,
                        'email'    => $application->email,
                        'username' => $username,
                        'password' => Hash::make($tempPassword),
                        'role'     => 3, // Citizen Role
                    ]);

                    // --- STEP C: CREATE MASTERLIST ENTRY ---
                    // Siniguradong lahat ng fields ay fillable sa Model
                    Masterlist::create([
                        'user_id'           => $newUser->id,
                        'id_status'         => $application->id_status,
                        'scid_number'       => $scidNumber,
                        'first_name'        => $application->first_name,
                        'last_name'         => $application->last_name,
                        'email'             => $application->email,
                        'barangay'          => $application->barangay,
                        'city_municipality' => $application->city_municipality,
                        'province'          => $application->province,
                        'district'          => $application->district,
                        'birthdate'         => $application->birthdate,
                        'gender'            => $application->gender,
                        'civil_status'      => $application->civil_status,
                        'citizenship'       => $application->citizenship,
                        'status'            => 'Active',
                        'date_submitted'    => $application->date_submitted ?? now(),
                    ]);
                }
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Application updated. If approved, user account and masterlist entry were created.'
        ]);

    } catch (\Exception $e) {
        // I-return ang specific error message para madali i-debug
        return response()->json(['error' => 'Failed: ' . $e->getMessage()], 500);
    }
}
    /**
     * DELETE /api/applications/{application}
     */
    public function destroy(Request $request, Application $application)
{
    // 1. Kilalanin ang user at kunin ang role name
    $user = $request->user()->load('roleRelation');
    $roleName = strtolower(optional($user->roleRelation)->name);

    // 2. I-define ang authorized roles para sa pagbubura
    $authorizedRoles = ['admin', 'super admin'];

    // 3. Security Guard: Check kung authorized ang role
    if (!in_array($roleName, $authorizedRoles)) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized: Only Admins and Super Admins can delete application records.',
            'your_role' => $roleName
        ], 403); // 403 Forbidden
    }

    // 4. Isagawa ang pagbura kung pumasa sa role check
    $application->delete();

    return response()->json([
        'status' => 'success',
        'message' => 'Application record has been successfully deleted.'
    ], 200);
}
}