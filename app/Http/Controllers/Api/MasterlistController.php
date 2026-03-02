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
            'email'             => 'required|email',
            'gender'            => 'required|string',
            'birthdate'         => 'required|date',
            'birthplace'        => 'required|string',
            'contact_number'    => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $validated) {
            $validated['user_id'] = $request->user()->id;
            $validated['date_submitted'] = now();
            
            // STEP 1: Default status is 'new' upon entry to masterlist
            $validated['id_status'] = $validated['id_status'] ?? 'new';

            $record = Masterlist::create($validated);

            // STEP 2: TRIGGER POINT - Kung sakaling isinave ito agad bilang 'pending'
            if ($record->id_status === 'pending') {
                $this->triggerIdIssuance($record);
            }

            return response()->json(['status' => 'success', 'data' => $record], 201);
        });
    }

    /**
     * PUT /api/masterlist/{id}
     * TRIGGER: Kapag ang status ay binago patungong 'pending'.
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
     * Nilulutas nito ang Error 1364 at Error 1054
     */
   private function triggerIdIssuance($record)
{
    IdIssuance::updateOrCreate(
        ['scid_number' => $record->scid_number],
        [
            'status'                => 'pending',
            'date_applied'          => now(),
            // Idagdag ang mga fields na ito para mawala ang Error 1364
            'last_name'             => $record->last_name, 
            'first_name'            => $record->first_name,
            'middle_name'            => $record->middle_name,
            'birthdate'            => $record->birthdate,
            'place_of_birth'            => $record->birthplace,
            'house_no'                  => $record->house_no,
            'street'                  => $record->street,
            'barangay'                => $record->barangay,
            'city_municipality'       => $record->city_municipality,
            'province'       => $record->province,
            'district'       => $record->district,
            'citizenship'    => $record->citizenship,
            'civil_status'   => $record->civil_status,
            'willing_member' => $record->willing_member,
            'emergency_contact_person' => $record->contact_person,
            'emergency_contact_number' => $record->contact_number ?? 'N/A', 
            // Fix para sa timestamp columns (Error 1054)
            'date_created'          => now(),
            'last_updated'          => now(),
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