<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IdIssuance;
use App\Models\Masterlist;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class IdIssuanceController extends Controller
{
    /**
     * GET /api/id-issuances
     * Ipakita ang records base sa role ng user.
     */
    public function index(Request $request)
    {
        $user = $request->user()->load('roleRelation'); 
        $roleName = strtolower($user->roleRelation->name ?? '');

        if (in_array($roleName, ['admin', 'super admin'])) {
            $data = IdIssuance::all(); 
        } else {
            // I-filter gamit ang user_id (Integer)
            $data = IdIssuance::where('user_id', $user->id)->get();
        }

        return response()->json([
            'status' => 'success',
            'role_accessed' => $roleName,
            'data' => $data
        ]);
    }

    /**
     * PUT /api/id-issuances/{id}
     * SYNC: Kapag nag-update dito, dapat mag-update din sa Masterlist.
     */
    public function update(Request $request, $id)
    {
        $issuance = IdIssuance::findOrFail($id);

        return DB::transaction(function () use ($request, $issuance) {
            // 1. I-update ang IdIssuance (Gumagamit na ng 'id_status' base sa bagong columns)
            $issuance->update($request->all());

            // 2. TWO-WAY SYNC: I-update ang Masterlist record gamit ang scid_number
            Masterlist::where('scid_number', $issuance->scid_number)
                ->update([
                // Sinisiguro na ang bagong status ay lilipat sa Masterlist
                'id_status'    => $issuance->id_status, 
                'last_updated' => now(),
                ]);

           return response()->json([
            'status' => 'success',
            'message' => 'Both tables are now synchronized.',
            'synced_status' => $issuance->id_status
            ]);
        });
    }

    /**
     * POST /api/id-issuances
     * Mag-save ng bagong ID issuance record (Citizen side).
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Kunin ang citizen_id mula sa Masterlist gamit ang user_id
        $masterlist = Masterlist::where('user_id', $user->id)->first();

        if (!$masterlist) {
            return response()->json([
                'status' => 'error',
                'message' => 'User record not found in Masterlist.'
            ], 404);
        }

        // 1. Validation base sa bagong columns
        $validated = $request->validate([
            'scid_number'              => 'required|string',
            'gender'                   => 'required|string|max:255',
            'contact_number'           => 'required|string',
            'last_name'                => 'required|string|max:255',
            'first_name'               => 'required|string|max:255',
            'middle_name'              => 'nullable|string|max:255',
            'suffix'                   => 'nullable|string|max:255',
            'birthdate'                => 'required|date', 
            'place_of_birth'           => 'required|string',
            'age'                      => 'required|integer',
            'house_no'                 => 'required|string',
            'street'                   => 'required|string',
            'barangay'                 => 'required|string',
            'city_municipality'        => 'required|string',
            'province'                 => 'required|string',
            'district'                 => 'required|string',
            'citizenship'              => 'required|string',
            'civil_status'             => 'required|string',
            'emergency_contact_person' => 'required|string',
            'emergency_contact_number' => 'required|string',
            'willing_member'           => 'required|string',
            'id_request_type'          => 'required|string',
            'id_modality'              => 'required|string',
            'photo_url'                => 'required|string',
            'req1_url'                 => 'required|string',
            'req2_url'                 => 'required|string',
        ]);

        // 2. Automatic Assignments
        $validated['user_id']      = $user->id; 
        $validated['citizen_id']   = $masterlist->citizen_id; 
        $validated['id_status']    = 'Pending'; // Default status
        $validated['application_date'] = now(); // In-rename mula 'submitted_date'

        $issuance = IdIssuance::create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'ID Issuance record created successfully.',
            'data'    => $issuance
        ], 201);
    }

    /**
     * GET /api/id-issuances/{id}
     */
    public function show($id)
    {
        // Ginamit na ang 'id' (Primary Key) sa halip na 'issuance_id'
        $record = IdIssuance::where('id', $id)
                            ->where('user_id', auth()->id())
                            ->firstOrFail();

        return response()->json(['status' => 'success', 'data' => $record]);
    }

    /**
     * DELETE /api/id-issuances/{id}
     */
    public function destroy($id)
    {
        $record = IdIssuance::where('id', $id)
                            ->where('user_id', auth()->id())
                            ->first();

        if (!$record) {
            return response()->json(['message' => 'Record not found or unauthorized.'], 404);
        }

        $record->delete();

        return response()->json(['message' => 'Deleted successfully.'], 200);
    }
}