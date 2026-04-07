<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\User;
use App\Models\Masterlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Exception;

class ApplicationsController extends Controller
{
    /**
     * GET /api/applications
     * Filtered: Admin sees all, Citizen sees only their own.
     */
    public function index(Request $request)
    {
        $user = $request->user()->load('roleRelation');
        $roleName = strtolower(optional($user->roleRelation)->name);
        
        $query = Application::query();

        if (in_array($roleName, ['admin', 'super admin'])) {
            $status = $request->query('status', 'All');
            if ($status !== 'All') {
                $query->where('reg_status', $status);
            }
        } else {
            $query->where('user_id', $user->id);
        }

        $applications = $query->orderBy('date_created', 'desc')->paginate(15);

        return response()->json([
            'status' => true,
            'role' => $roleName,
            'data' => $applications
        ]);
    }

    /**
     * POST /api/applications
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'last_name'         => 'required|string|max:255',
            'first_name'        => 'required|string|max:255',
            'middle_name'       => 'nullable|string|max:255',
            'suffix'            => 'nullable|string|max:10',
            'birth_date'        => 'required|date',
            'age'               => 'required|integer',
            'sex'               => 'required|string',
            'civil_status'      => 'required|string',
            'citizenship'       => 'required|string',
            'birth_place'       => 'required|string',
            'address'           => 'required|string',
            'barangay'          => 'required|string',
            'city_municipality' => 'required|string',
            'district'          => 'required|string',
            'province'          => 'required|string',
            'email'             => 'required|email|max:255|unique:applications,email',
            'contact_number'    => 'required|string|max:20|unique:applications,contact_number',
            'living_arrangement'=> 'required|string',
            'is_pensioner'      => 'nullable|boolean',
            'pension_amount'    => 'nullable|numeric',
            'has_illness'       => 'required|boolean',
            'registration_type' => 'required|string',
            'document'          => 'required|array|min:1',
            'document.*'        => 'file|mimes:pdf,jpg,jpeg,png,docx|max:10240',
        ], [
            'email.unique' => 'Duplicate email',
            'contact_number' => 'Duplicate contact_number'
        ]);

        $fileMetaData = [];
        if ($request->hasFile('document')) {
            foreach ($request->file('document') as $file) {
                $path = $file->store('attachments', 'public');
                $fileMetaData[] = [
                    'filename' => $file->getClientOriginalName(),
                    'path'     => $path
                ];
            }
        }

        $validated['user_id'] = Auth::id(); 
        $validated['document'] = json_encode($fileMetaData);
        $validated['reg_status'] = 'Pending';
        $validated['date_created'] = now();

        $application = Application::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Application submitted successfully.',
            'id' => $application->id
        ], 201);
    }

    /**
     * PUT /api/applications/{id}
     * Pinahusay: Kasama ang Password Generation at has_changed flag.
     */
    public function update(Request $request, Application $application)
    {
        $user = $request->user()->load('roleRelation');
        $roleName = strtolower(optional($user->roleRelation)->name);
        $isAdmin = in_array($roleName, ['admin', 'super admin']);
        
        if (!$isAdmin && $application->reg_status === 'approved') {
            return response()->json(['status' => 'error', 'message' => 'Approved applications cannot be edited.'], 403);
        }

        $validated = $request->validate([
            'reg_status'        => 'sometimes|required|in:approved,disapproved,pending',
            'rejection_remarks' => 'required_if:reg_status,disapproved|string|nullable',
            'first_name'        => 'sometimes|required|string|max:255',
            'last_name'         => 'sometimes|required|string|max:255',
            'middle_name'       => 'nullable|string|max:255',
            'suffix'            => 'nullable|string|max:10',
            'email'             => 'sometimes|required|email|unique:applications,email,' . $application->id,
            'contact_number'    => 'sometimes|required|string|unique:applications,contact_number,' . $application->id,
            'address'           => 'sometimes|required|string',
            'barangay'          => 'sometimes|required|string',
            'city_municipality' => 'sometimes|required|string',
            'province'          => 'sometimes|required|string',
            'district'          => 'sometimes|required|string',
            'birth_date'        => 'sometimes|required|date',
            'age'               => 'sometimes|required|integer',
            'sex'               => 'sometimes|required|string',
            'civil_status'      => 'sometimes|required|string',
            'citizenship'       => 'sometimes|required|string',
            'living_arrangement'=> 'sometimes|required|string',
            'is_pensioner'      => 'nullable|boolean',
            'pension_amount'    => 'nullable|numeric',
            'has_illness'       => 'sometimes|required|boolean',
        ]);

        $tempPassword = null;
        $finalUsername = null;

        try {
            DB::transaction(function () use ($request, $application, $validated, $isAdmin, &$tempPassword, &$finalUsername) {
                
                if ($isAdmin && isset($validated['reg_status'])) {
                    $validated['reviewed_by'] = $request->user()->id;
                    $validated['date_reviewed'] = now();
                }

                $application->update($validated);

                if (isset($validated['reg_status']) && strtolower($validated['reg_status']) === 'approved') {
                    
                    $existingUser = User::where('email', $application->email)->first();
                    
                    if (!$existingUser) {
                        // Logic: sc + 4 numbers
                        $tempPassword = 'sc' . rand(1000, 9999);
                        
                        $finalUsername = Str::slug($application->first_name . $application->last_name, '.') . '.' . rand(100, 999);
                        while (User::where('username', $username ?? $finalUsername)->exists()) {
                            $finalUsername = Str::slug($application->first_name . $application->last_name, '.') . '.' . rand(100, 999);
                        }

                        $newUser = User::create([
                            'name'        => $application->first_name . ' ' . $application->last_name,
                            'email'       => $application->email,
                            'username'    => $finalUsername,
                            'password'    => Hash::make($tempPassword),
                            'has_changed' => 0, // 0 as requested for temporary state
                            'role'        => 3,
                        ]);
                        $targetUserId = $newUser->id;
                    } else {
                        $targetUserId = $existingUser->id;
                        $finalUsername = $existingUser->username;
                    }

                    $scidNumber = now()->year . '-' . rand(10000, 99999);
                    while (Masterlist::where('scid_number', $scidNumber)->exists()) {
                        $scidNumber = now()->year . '-' . rand(10000, 99999);
                    }

                    $updatedApp = $application->fresh();

                    Masterlist::create([
                        'application_id'    => $updatedApp->id,
                        'user_id'           => $targetUserId,
                        'username'          => $finalUsername,
                        'temp_password'     => $tempPassword, // Plain password as requested for database
                        'scid_number'       => $scidNumber,
                        'first_name'        => $updatedApp->first_name,
                        'middle_name'       => $updatedApp->middle_name,
                        'last_name'         => $updatedApp->last_name,
                        'suffix'            => $updatedApp->suffix,
                        'birth_date'        => $updatedApp->birth_date,
                        'age'               => $updatedApp->age,
                        'sex'               => $updatedApp->sex,
                        'civil_status'      => $updatedApp->civil_status,
                        'citizenship'       => $updatedApp->citizenship,
                        'birth_place'       => $updatedApp->birth_place,
                        'district'          => $updatedApp->district,
                        'address'           => $updatedApp->address,
                        'barangay'          => $updatedApp->barangay,
                        'city_municipality' => $updatedApp->city_municipality,
                        'province'          => $updatedApp->province,
                        'email'             => $updatedApp->email,
                        'contact_number'    => $updatedApp->contact_number,
                        'living_arrangement'=> $updatedApp->living_arrangement,
                        'is_pensioner'      => $updatedApp->is_pensioner,
                        'pension_amount'    => $updatedApp->pension_amount,
                        'has_illness'       => $updatedApp->has_illness,
                        'id_status'         => 'new',
                        'document'          => $updatedApp->document, 
                        'registration_date' => now(),
                    ]);
                }
            });

            $isApproved = (isset($validated['reg_status']) && $validated['reg_status'] === 'approved');
            
            $response = [
                'status'  => 'success',
                'message' => $isApproved ? 'Application approved and account created.' : 'Application details updated successfully.',
            ];

            // Response credentials object for frontend
            if ($isApproved && $tempPassword) {
                $response['credentials'] = [
                    'username'           => $finalUsername,
                    'temporary_password' => $tempPassword,
                    'has_changed'        => 0,
                    'note'               => 'Please provide these credentials to the citizen.'
                ];
            }

            return response()->json($response);

        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to update: ' . $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/applications/{id}
     */
    public function show(Request $request, Application $application)
    {
        $user = $request->user()->load('roleRelation');
        $roleName = strtolower(optional($user->roleRelation)->name);

        if (!in_array($roleName, ['admin', 'super admin']) && $application->user_id !== $user->id) {
            return response()->json(['status' => false, 'message' => 'Unauthorized access.'], 403);
        }

        return response()->json(['status' => true, 'data' => $application]);
    }

    /**
     * DELETE /api/applications/{id}
     */
    public function destroy(Request $request, Application $application)
    {
        $user = $request->user()->load('roleRelation');
        if (!in_array(strtolower(optional($user->roleRelation)->name), ['admin', 'super admin'])) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
        }

        $application->delete();
        return response()->json(['status' => 'success', 'message' => 'Application deleted.']);
    }
}