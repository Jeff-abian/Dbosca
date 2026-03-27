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

        // LOGIC: Filter by Role
        if (in_array($roleName, ['admin', 'super admin'])) {
            $status = $request->query('status', 'All');
            if ($status !== 'All') {
                $query->where('reg_status', $status);
            }
        } else {
            // Kung Citizen, sariling record lang ang kukunin
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
     * Pag-save ng application na may Multiple File Hashing.
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
            'documents'         => 'required|array|min:1',
            'documents.*'       => 'file|mimes:pdf,jpg,jpeg,png,docx|max:10240',
        ], [
        // Custom Error Message para alam ni user kung bakit na-reject
        'email.unique' => 'Duplicate email',
        'contact_number' => 'Duplicate contact_number'
    ]);

        // File Metadata (Array of Objects: Filename + Hashed Path)
        $fileMetaData = [];
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $path = $file->store('attachments', 'public');
                $fileMetaData[] = [
                    'filename' => $file->getClientOriginalName(),
                    'path'     => $path
                ];
            }
        }

        // Attach user_id para sa filtering mamaya
        $validated['user_id'] = Auth::id(); 
        $validated['reg_attachments'] = json_encode($fileMetaData);
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
     * GET /api/applications/{id}
     * Security: Di pwedeng silipin ng ibang user ang data ng iba.
     */
    public function show(Request $request, Application $application)
    {
        $user = $request->user()->load('roleRelation');
        $roleName = strtolower(optional($user->roleRelation)->name);

        // Security Check: Admin or Owner only
        if (!in_array($roleName, ['admin', 'super admin']) && $application->user_id !== $user->id) {
            return response()->json(['status' => false, 'message' => 'Unauthorized access.'], 403);
        }

        return response()->json([
            'status' => true,
            'data' => $application
        ]);
    }

    /**
     * PUT /api/applications/{id}
     * Approval and Masterlist Syncing.
     */
    public function update(Request $request, Application $application)
    {
        $user = $request->user()->load('roleRelation');
        $roleName = strtolower(optional($user->roleRelation)->name);
        
        if (!in_array($roleName, ['admin', 'super admin'])) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'reg_status' => 'required|in:approved,disapproved,Pending',
            'rejection_remarks' => 'required_if:reg_status,disapproved|string|nullable',
        ]);

        try {
            DB::transaction(function () use ($request, $application, $validated) {
    // 1. Update Application status
    $application->update([
        'reg_status' => $validated['reg_status'],
        'reviewed_by' => $request->user()->id,
        'date_reviewed' => now(),
    ]);

    if (strtolower($validated['reg_status']) === 'approved') {
        
        $existingUser = User::where('email', $application->email)->first();
        
        // DITO NATIN KUKUNIN O GAGAWA NG USERNAME
        if (!$existingUser) {
            // GENERATE NEW USERNAME
            $username = Str::slug($application->first_name . $application->last_name, '.') . '.' . rand(100, 999);
            while (User::where('username', $username)->exists()) {
                $username = Str::slug($application->first_name . $application->last_name, '.') . '.' . rand(100, 999);
            }

            $newUser = User::create([
                'name'     => $application->first_name . ' ' . $application->last_name,
                'email'    => $application->email,
                'username' => $username, // Na-save na sa users table
                'password' => Hash::make('User' . now()->year . '!'),
                'role'     => 3,
            ]);
            $targetUserId = $newUser->id;
            $finalUsername = $newUser->username; // Ito ang ipapasa natin sa masterlist
        } else {
            $targetUserId = $existingUser->id;
            $finalUsername = $existingUser->username; // Gamitin ang luma kung existing na
        }

                    // Generate Unique SCID Number
                    $scidNumber = now()->year . '-' . rand(10000, 99999);
                    while (Masterlist::where('scid_number', $scidNumber)->exists()) {
                        $scidNumber = now()->year . '-' . rand(10000, 99999);
                    }

                    // MASTERLIST INSERT (Copying Data)
                    Masterlist::create([
                        'application_id'    => $application->id,
                        'user_id'           => $targetUserId,
                        'username'          => $finalUsername, // <--- ETO YUNG NAIDAGDAG
                        'scid_number'       => $scidNumber,
                        'first_name'        => $application->first_name,
                        'middle_name'       => $application->middle_name,
                        'last_name'         => $application->last_name,
                        'suffix'            => $application->suffix,
                        'birth_date'        => $application->birth_date,
                        'age'               => $application->age,
                        'sex'               => $application->sex,
                        'civil_status'      => $application->civil_status,
                        'citizenship'       => $application->citizenship,
                        'birth_place'       => $application->birth_place,
                        'district'          => $application->district,
                        'address'           => $application->address,
                        'barangay'          => $application->barangay,
                        'city_municipality' => $application->city_municipality,
                        'province'          => $application->province,
                        'email'             => $application->email,
                        'contact_number'    => $application->contact_number,
                        'living_arrangement'=> $application->living_arrangement,
                        'is_pensioner'      => $application->is_pensioner,
                        'pension_amount'    => $application->pension_amount,
                        'has_illness'       => $application->has_illness,
                        'id_status'         => 'new',
                        'document_path'     => $application->reg_attachments, 
                        'registration_date' => now(),
                    ]);
                }
            });

            return response()->json(['status' => 'success', 'message' => 'Synced to Masterlist successfully.']);

        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to update: ' . $e->getMessage()], 500);
        }
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