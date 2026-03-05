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
     * Ginagamit ang 'reg_status' at 'date_created' para sa sorting.
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

        // Updated: 'reg_status' ang column name na ngayon
        if ($status !== 'All') {
            $query->where('reg_status', $status);
        }

        // Fix para sa Timeout: Paginate(15) at tamang sorting column
        $applications = $query->orderBy('date_created', 'desc')->paginate(15);

        return response()->json([
            'status' => true,
            'viewing_status' => $status,
            'data' => $applications
        ]);
    }

    /**
     * POST /api/applications
     * Updated validation based sa iyong bagong column list.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'last_name'                  => 'required|string|max:255',
            'first_name'                 => 'required|string|max:255',
            'middle_name'                => 'nullable|string|max:255',
            'suffix'                     => 'nullable|string|max:10',
            'birth_date'                 => 'required|date',
            'age'                        => 'required|integer',
            'sex'                        => 'required|string',
            'civil_status'               => 'required|string',
            'citizenship'                => 'required|string',
            'birth_place'                => 'required|string',
            'address'                    => 'required|string',
            'barangay'                   => 'required|string',
            'city_municipality'          => 'required|string',
            'district'                   => 'required|string',
            'province'                   => 'required|string',
            'email'                      => 'required|email|max:255',
            'contact_number'             => 'required|string|max:20',
            'living_arrangement'         => 'required|string',
            'is_pensioner'               => 'nullable|boolean',
            'pension_source_gsis'        => 'nullable|boolean',
            'pension_source_sss'         => 'nullable|boolean',
            'pension_source_afpslai'     => 'nullable|boolean',
            'pension_source_others'      => 'nullable|string',
            'pension_amount'             => 'nullable|numeric',
            'has_permanent_income'       => 'nullable|boolean',
            'permanent_income_source'    => 'nullable|string',
            'has_regular_support'        => 'nullable|boolean',
            'support_type_cash'          => 'nullable|boolean',
            'support_cash_amount'        => 'nullable|numeric',
            'support_cash_frequency'     => 'nullable|string',
            'support_type_inkind'        => 'nullable|boolean',
            'kind_support_details'       => 'nullable|string',
            'has_illness'                => 'required|boolean',
            'illness_details'            => 'nullable|string',
            'hospitalized_last_6_months' => 'nullable|boolean',
            'registration_type'          => 'required|string',
            'document_path'              => 'nullable|file|mimes:pdf,jpg,png|max:5120',
        ]);

        if ($request->hasFile('document_path')) {
            $validated['document_path'] = $request->file('document_path')->store('attachments', 'public');
        }

        $validated['reg_status'] = 'Pending';
        $validated['date_created'] = now();

        Application::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Application submitted successfully.',
        ], 201);
    }

    /**
     * PUT /api/applications/{application}
     * TRIGGER: Pag-sync ng data sa Masterlist gamit ang bagong column names.
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
                
                // 1. I-update ang Application status (Gamit ang reg_status at rejection_remarks)
                $application->update([
                    'reg_status'        => $validated['reg_status'],
                    'reviewed_by'       => $request->user()->id,
                    'date_reviewed'     => now(),
                    'rejection_remarks' => $validated['rejection_remarks'] ?? null,
                ]);

                // 2. Logic kapag APPROVED - Paglipat sa Masterlist
                if (strtolower($validated['reg_status']) === 'approved') {
                    
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
                            'role'     => 3, // Citizen
                        ]);
                        $targetUserId = $newUser->id;
                    }

                    // Generate Unique SCID
                    $scidNumber = now()->year . '-' . rand(10000, 99999);
                    while (Masterlist::where('scid_number', $scidNumber)->exists()) {
                        $scidNumber = now()->year . '-' . rand(10000, 99999);
                    }

                    // 3. Create Masterlist Entry (Mapping sa bagong columns)
                    // Nilulutas nito ang Error 1364 sa pamamagitan ng pagpasa ng lahat ng kailangang fields.
    Masterlist::create([
    'user_id'           => $targetUserId,
    'id_status'         => 'new',
    'scid_number'       => $scidNumber,
    'first_name'        => $application->first_name,
    'middle_name'       => $application->middle_name,
    'last_name'         => $application->last_name,
    'suffix'            => $application->suffix,
    
    // DAGDAGAN ITONG MGA SUMUSUNOD (Eto ang mga kulang base sa SQL error mo):
    'birth_date'        => $application->birth_date,  // Siguraduhing may underscore
    'birth_place'       => $application->birth_place, // Siguraduhing may underscore
    'sex'               => $application->sex,
    
    'age'               => $application->age,
    'civil_status'      => $application->civil_status,
    'citizenship'       => $application->citizenship,
    'address'           => $application->address,
    'barangay'          => $application->barangay,
    'city_municipality' => $application->city_municipality,
    'province'          => $application->province,
    'district'          => $application->district,
    'contact_number'    => $application->contact_number,
    'email'             => $application->email,
    'document_path'     => $application->document_path,
    'date_created'      => now(),
    'last_updated'      => now(),
                    ]);
                }
            });

            return response()->json([
                'status' => 'success', 
                'message' => 'Application updated and synced to Masterlist.'
            ]);

        } catch (Exception $e) {
            return response()->json(['error' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, Application $application)
    {
        if (!in_array(strtolower($request->user()->roleRelation->name), ['admin', 'super admin'])) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
        }
        $application->delete();
        return response()->json(['status' => 'success', 'message' => 'Deleted.']);
    }
}