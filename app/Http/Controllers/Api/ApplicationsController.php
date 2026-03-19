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
    // ... (index method remains the same)

    public function store(Request $request)
    {
        // 1. Validation
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
            'email'             => 'required|email|max:255',
            'contact_number'    => 'required|string|max:20',
            'living_arrangement'=> 'required|string',
            'is_pensioner'      => 'nullable|boolean',
            'pension_amount'    => 'nullable|numeric',
            'has_illness'       => 'required|boolean',
            'registration_type' => 'required|string',
            'documents'         => 'required|array|min:1',
            'documents.*'       => 'file|mimes:pdf,jpg,jpeg,png,docx|max:10240',
        ]);

        // 2. Revised File Handling: Array of Objects (Filename + Hashed Path)
        $fileMetaData = [];
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                // I-store ang file (automatic hashed name para iwas duplication)
                $path = $file->store('attachments', 'public');
                
                // I-save ang original name kasama ang hashed path
                $fileMetaData[] = [
                    'filename' => $file->getClientOriginalName(),
                    'path'     => $path
                ];
            }
        }

        // 3. Prepare data for insertion
        $validated['reg_attachments'] = json_encode($fileMetaData);
        $validated['reg_status'] = 'Pending';
        $validated['date_created'] = now();

        // 4. Single creation call
        $application = Application::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Application submitted successfully.',
            'id' => $application->id
        ], 201);
    }

    public function update(Request $request, Application $application)
    {
        // Role check (keep as is)
        $user = $request->user()->load('roleRelation');
        if (!in_array(strtolower(optional($user->roleRelation)->name), ['admin', 'super admin'])) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'reg_status' => 'required|in:approved,disapproved,Pending',
            'rejection_remarks' => 'required_if:reg_status,disapproved|string|nullable',
        ]);

        try {
            DB::transaction(function () use ($request, $application, $validated) {
                
                $application->update([
                    'reg_status'        => $validated['reg_status'],
                    'reviewed_by'       => $request->user()->id,
                    'date_reviewed'     => now(),
                    'rejection_remarks' => $validated['rejection_remarks'] ?? null,
                ]);

                if (strtolower($validated['reg_status']) === 'approved') {
                    
                    // User Creation logic (Keep as is)
                    $existingUser = User::where('email', $application->email)->first();
                    $targetUserId = $existingUser ? $existingUser->id : null;

                    if (!$existingUser) {
                        $tempPassword = 'User' . now()->year . '!'; 
                        $username = Str::slug($application->first_name . $application->last_name, '.') . '.' . rand(100, 999);

                        $newUser = User::create([
                            'name'     => $application->first_name . ' ' . $application->last_name,
                            'email'    => $application->email,
                            'username' => $username,
                            'password' => Hash::make($tempPassword),
                            'role'     => 3, 
                        ]);
                        $targetUserId = $newUser->id;
                    }

                    // SCID Generation (Keep as is)
                    $scidNumber = now()->year . '-' . rand(10000, 99999);
                    while (Masterlist::where('scid_number', $scidNumber)->exists()) {
                        $scidNumber = now()->year . '-' . rand(10000, 99999);
                    }

                    // 5. MASTERLIST SYNC
                    Masterlist::create([
                        'application_id'    => $application->id,
                        'user_id'           => $targetUserId,
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
                        'document_path'     => $application->reg_attachments, // Kopya ng JSON Metadata
                        'registration_date' => now(),
                    ]);
                }
            });

            return response()->json(['status' => 'success', 'message' => 'Synced to Masterlist.']);

        } catch (Exception $e) {
            return response()->json(['error' => 'Failed: ' . $e->getMessage()], 500);
        }
    }
    // ... (destroy method remains the same)
}