<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Masterlist;
use App\Models\IdIssuance; 
use App\Http\Resources\MasterlistResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterlistController extends Controller
{
    private function isAuthorizedAdmin(Request $request)
    {
        $user = $request->user()->load('roleRelation');
        $roleName = strtolower(optional($user->roleRelation)->name);
        return in_array($roleName, ['admin', 'super admin']);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $roleName = strtolower($user->roleRelation->name ?? '');

        if (in_array($roleName, ['admin', 'super admin'])) {
            $data = Masterlist::all();
        } else {
            $data = Masterlist::where('user_id', $user->id)->get();
        }

        if ($data->isEmpty()) {
            return response()->json(['message' => 'No records found.'], 200);
        }

        return MasterlistResource::collection($data);
    }

    /**
     * POST /api/masterlist
     * DEFAULT: Ang id_status ay itatakda bilang 'new' sa pag-create.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'scid_number'       => 'required|string|unique:masterlist,scid_number',
            'id_status'         => 'nullable|in:new,pending,approved,rejected,released',
            'citizenship'       => 'required|string',
            'barangay'          => 'required|string',
            'city_municipality' => 'required|string',
            'civil_status'      => 'required|string',
            'district'          => 'required|string',
            'province'          => 'required|string',
            'last_name'         => 'required|string',
            'first_name'        => 'required|string',
            'middle_name'       => 'nullable|string',
            'suffix'            => 'nullable|string',
            'email'             => 'required|email',
            'sex'               => 'required|string', // In-rename mula 'gender'
            'birth_date'        => 'required|date',   // In-rename mula 'birthdate'
            'birth_place'       => 'required|string', // In-rename mula 'birthplace'
            'contact_number'    => 'nullable|string',
            'address'           => 'required|string',
            'age'               => 'required|integer',
        ]);

        return DB::transaction(function () use ($request, $validated) {
            $validated['user_id'] = $request->user()->id;
            $validated['registration_date'] = now(); // In-rename mula 'date_submitted'
            
            // STEP 1: Default status is 'new' upon entry to masterlist
            $validated['id_status'] = $validated['id_status'] ?? 'new';

            $record = Masterlist::create($validated);

            // STEP 2: TRIGGER POINT - Kapag 'pending' ang status, gawa ng entry sa IdIssuance
            if ($record->id_status === 'pending') {
                $this->triggerIdIssuance($record);
            }

            return response()->json(['status' => 'success', 'data' => $record], 201);
        });
    }

    /**
     * PUT /api/masterlist/{id}
     * Hahanapin ang record gamit ang updated primary key 'citizen_id'
     */
    public function update(Request $request, $id)
    {
        // Gagamit na tayo ng citizen_id sa pag-find base sa bagong columns
        $record = Masterlist::where('citizen_id', $id)->first();

        if (!$record) {
            return response()->json(['message' => 'Record not found.'], 404);
        }

        if (!$this->isAuthorizedAdmin($request) && $record->user_id != $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return DB::transaction(function () use ($request, $record) {
            $oldStatus = $record->id_status;
            $record->update($request->all());

            // TRIGGER POINT: Transition to 'pending'
            if ($oldStatus !== 'pending' && $record->id_status === 'pending') {
                $this->triggerIdIssuance($record);
            }

            return response()->json(['status' => 'success', 'data' => $record]);
        });
    }

    /**
     * Helper function para sa ID Issuance Entry
     * Mapping mula Masterlist updated columns patungong IdIssuance
     */
    private function triggerIdIssuance($record)
{
    IdIssuance::updateOrCreate(
        ['scid_number' => $record->scid_number],
        [
            'user_id'                  => $record->user_id,
            'citizen_id'               => $record->citizen_id,
            'id_status'                => 'pending',
            'application_date'         => now(),
            
            // Mapping ng Personal Data (Iwas Error 1364)
            'first_name'               => $record->first_name,
            'middle_name'              => $record->middle_name ?? '',
            'last_name'                => $record->last_name,
            'suffix'                   => $record->suffix ?? '',
            'gender'                   => $record->sex,        // Masterlist 'sex' -> Issuance 'gender'
            'birthdate'                => $record->birth_date, // Masterlist 'birth_date' -> Issuance 'birthdate'
            'place_of_birth'           => $record->birth_place, // Masterlist 'birth_place' -> Issuance 'place_of_birth'
            'age'                      => $record->age,
            'contact_number'           => $record->contact_number,
            
            // Mapping ng Address (Dito nag-error kanina)
            'house_no'                 => $record->house_no ?? 'N/A', // Siguraduhing may value
            'street'                   => $record->street ?? 'N/A',
            'barangay'                 => $record->barangay,
            'city_municipality'        => $record->city_municipality,
            'province'                 => $record->province,
            'district'                 => $record->district,
            
            // Iba pang mandatory fields
            'citizenship'              => $record->citizenship,
            'civil_status'             => $record->civil_status,
            'emergency_contact_person' => $record->emergency_contact_person ?? 'N/A',
            'emergency_contact_number' => $record->emergency_contact_number ?? 'N/A',
            // CONVERSION LOGIC: Baguhin ang 'Yes/No' patungong 1/0
            'willing_member' => ($record->willing_member === 'Yes' || $record->willing_member == 1) ? 1 : 0,
            
            // Default Tracking Columns
            'date_created'             => now(),
            'last_updated'             => now(),
        ]
    );
}

    public function destroy(Request $request, $id)
    {
        $record = Masterlist::where('citizen_id', $id)->first();
        if (!$record) return response()->json(['message' => 'Not found.'], 404);
        if (!$this->isAuthorizedAdmin($request)) return response()->json(['message' => 'Admin only.'], 403);

        $record->delete();
        return response()->json(['status' => 'success', 'message' => 'Deleted.']);
    }
}