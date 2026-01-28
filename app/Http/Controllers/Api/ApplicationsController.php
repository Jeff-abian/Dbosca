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
            'citizen_id' => 'required|string', // Siguraduhing kasama ito para sa Masterlist
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
        $user = $request->user()->load('roleRelation');
        $roleName = strtolower(optional($user->roleRelation)->name);

        if (!in_array($roleName, ['admin', 'super admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:Approved,Disapproved,Pending',
            'reason_for_disapproval' => 'required_if:status,Disapproved|string|nullable',
        ]);

        try {
            DB::transaction(function () use ($request, $application, $validated) {
                
                if ($validated['status'] === 'Approved') {
                    $tempPassword = 'User' . now()->year . '!'; 
                    $username = Str::slug($application->first_name . $application->last_name, '.') . '.' . rand(100, 999);

                    // 1. Gagawa ng account sa Users table (Role 3 = Citizen)
                    $newUser = User::create([
                        'name'     => $application->first_name . ' ' . $application->last_name,
                        'email'    => $application->email,
                        'username' => $username,
                        'password' => Hash::make($tempPassword),
                        'role'     => 3, 
                    ]);

                    $newUserId = $newUser->id; 

                    if (!$newUserId) {
                        throw new \Exception("Failed to retrieve new User ID.");
                    }

                    // 2. Ililipat ang data sa Masterlist table
                    Masterlist::create([
                        'user_id'           => $newUserId,
                        'citizen_id'        => $application->citizen_id,
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
                        'date_submitted'    => $application->date_submitted,
                    ]);

                    // 3. BURAHIN ang original application record dahil nasa Masterlist na ito
                    $application->delete();

                } else {
                    // Kung Disapproved o Pending, i-update lang ang record sa Application table
                    $application->update([
                        'status' => $validated['status'],
                        'reviewed_by' => $request->user()->name,
                        'date_reviewed' => now(),
                        'reason_for_disapproval' => $validated['reason_for_disapproval'] ?? null,
                    ]);
                }
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Application processed successfully.'
            ]);

        } catch (\Exception $e) {
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

        return response()->json(['status' => 'success', 'message' => 'Deleted successfully.'], 200);
    }
}