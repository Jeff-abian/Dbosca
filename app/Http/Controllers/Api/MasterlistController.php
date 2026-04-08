<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Masterlist;
use App\Models\IdIssuance; 
use App\Models\Application;
use App\Models\User;
use App\Http\Resources\MasterlistResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class MasterlistController extends Controller
{
    /**
     * Helper check para sa Admin/Super Admin
     */
    private function isAuthorizedAdmin(Request $request)
    {
        $user = $request->user()->load('roleRelation');
        $roleName = strtolower(optional($user->roleRelation)->name);
        return in_array($roleName, ['admin', 'super admin']);
    }

    /**
     * GET /api/masterlist
     */
    public function index(Request $request)
    {
        $user = $request->user()->load('roleRelation');
        $roleName = strtolower(optional($user->roleRelation)->name);

        $query = Masterlist::with('application');

        // Admin sees all, regular users see only their assigned records
        if (!in_array($roleName, ['admin', 'super admin'])) {
            $query->where('user_id', $user->id);
        }

        $data = $query->orderBy('registration_date', 'desc')->paginate(15);

        return MasterlistResource::collection($data);
    }

    /**
     * POST /api/masterlist
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Basic & Account Info
            'scid_number'       => 'required|string|unique:masterlist,scid_number',
            'application_id'    => 'nullable|integer',
            'username'          => 'nullable|string|max:255',
            'temp_password'     => 'nullable|string|max:255',
            'first_name'        => 'required|string|max:100',
            'middle_name'       => 'nullable|string|max:100',
            'last_name'         => 'required|string|max:100',
            'suffix'            => 'nullable|string|max:10',
            'birth_date'        => 'required|date',
            'age'               => 'nullable|integer',
            'sex'               => 'required|in:Male,Female',
            'civil_status'      => 'required|string|max:20',
            'citizenship'       => 'required|string|max:50',
            'birth_place'       => 'nullable|string|max:100',
            'address'           => 'required|string|max:150',
            'barangay'          => 'required|string|max:100',
            'city_municipality' => 'required|string|max:100',
            'district'          => 'nullable|string|max:50',
            'province'          => 'required|string|max:100',
            'email'             => 'nullable|email|max:100',
            'contact_number'    => 'required|string|max:20',
            'living_arrangement'=> 'nullable|string|max:100',

            // Pension Information
            'is_pensioner'           => 'nullable|boolean',
            'pension_amount'         => 'nullable|numeric',
            'pension_source_gsis'    => 'nullable|boolean',
            'pension_source_sss'     => 'nullable|boolean',
            'pension_source_afpslai' => 'nullable|boolean',
            'pension_source_others'  => 'nullable|string|max:150',
            
            // Income & Support
            'has_permanent_income'    => 'nullable|boolean',
            'permanent_income_source' => 'nullable|string|max:100',
            'has_regular_support'    => 'nullable|boolean',
            'support_type_cash'      => 'nullable|boolean',
            'support_cash_amount'    => 'nullable|numeric',
            'support_cash_frequency' => 'nullable|string|max:255',
            'support_type_inkind'    => 'nullable|boolean',
            'support_inkind_details' => 'nullable|string|max:255',

            // Health Information
            'has_illness'                => 'nullable|boolean',
            'illness_details'            => 'nullable|string',
            'hospitalized_last_6_months' => 'nullable|boolean',

            // Status & System Fields
            'registration_type' => 'required|string',
            'id_status'         => 'nullable|in:new,pending,approved,rejected,released',
            'vital_status'      => 'nullable|in:active,deceased',
            'date_of_death'     => 'nullable|date',
            'date_reviewed'     => 'nullable|date',
            'document'          => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $validated) {
            $validated['user_id'] = $request->user()->id;
            $validated['registration_date'] = now(); 
            $validated['date_reviewed'] = $validated['date_reviewed'] ?? now();
            $validated['id_status'] = $validated['id_status'] ?? 'new';

            $record = Masterlist::create($validated);

            // Trigger IdIssuance if status is pending
            if ($record->id_status === 'pending') {
                $this->triggerIdIssuance($record);
            }

            return response()->json(['status' => 'success', 'data' => $record], 201);
        });
    }

    /**
     * PUT /api/masterlist/{id}
     */
    public function update(Request $request, $id)
    {
        $record = Masterlist::where('citizen_id', $id)->first();

        if (!$record) {
            return response()->json(['message' => 'Record not found.'], 404);
        }

        if (!$this->isAuthorizedAdmin($request) && $record->user_id != $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return DB::transaction(function () use ($request, $record) {
            $oldStatus = $record->id_status;
            
            // Capture all fields for update
            $record->update($request->all());

            // Trigger IdIssuance if transitioning to 'pending'
            if ($oldStatus !== 'pending' && $record->id_status === 'pending') {
                $this->triggerIdIssuance($record);
            }

            return response()->json(['status' => 'success', 'data' => $record->fresh()]);
        });
    }

    /**
     * Helper para sa ID Issuance Mapping
     */
    private function triggerIdIssuance($record)
    {
        IdIssuance::updateOrCreate(
            ['scid_number' => $record->scid_number],
            [
                'user_id'           => $record->user_id,
                'citizen_id'        => $record->citizen_id,
                'id_status'         => 'pending',
                'application_date'  => now(),
                'first_name'        => $record->first_name,
                'middle_name'       => $record->middle_name ?? '',
                'last_name'         => $record->last_name,
                'suffix'            => $record->suffix ?? '',
                'gender'            => $record->sex, 
                'birthdate'         => $record->birth_date,
                'place_of_birth'    => $record->birth_place,
                'age'               => $record->age,
                'contact_number'    => $record->contact_number,
                'barangay'          => $record->barangay,
                'city_municipality' => $record->city_municipality,
                'province'          => $record->province,
                'district'          => $record->district,
                'citizenship'       => $record->citizenship,
                'civil_status'      => $record->civil_status,
                'last_updated'      => now(),
            ]
        );
    }

    /**
     * DELETE /api/masterlist/{id}
     */
    public function destroy(Request $request, $id)
    {
        $record = Masterlist::where('citizen_id', $id)->first();
        if (!$record) return response()->json(['message' => 'Not found.'], 404);
        if (!$this->isAuthorizedAdmin($request)) return response()->json(['message' => 'Admin only.'], 403);

        $record->delete();
        return response()->json(['status' => 'success', 'message' => 'Deleted.']);
    }

    /**
     * REVERSAL: Move back to Pending (Delete User & Masterlist, Reset Application)
     */
    public function moveToPending(Request $request, $id)
    {
        // Using findOrFail based on the ID passed
        $masterRecord = Masterlist::where('citizen_id', $id)->firstOrFail();

        try {
            return DB::transaction(function () use ($masterRecord) {
                $userIdToDelete = $masterRecord->user_id;

                // 1. Reset Application Status
                $application = Application::find($masterRecord->application_id);
                if ($application) {
                    $application->update(['reg_status' => 'Pending']);
                }

                // 2. Delete Masterlist Record (The Child)
                $masterRecord->delete();

                // 3. Delete User Account (The Parent)
                if ($userIdToDelete) {
                    User::where('id', $userIdToDelete)->delete();
                }

                return response()->json(['status' => 'success', 'message' => 'Successfully reversed to Pending.']);
            });

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}