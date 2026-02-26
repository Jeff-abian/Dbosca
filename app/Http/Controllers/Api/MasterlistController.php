<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Masterlist;
use App\Http\Resources\MasterlistResource;
use Illuminate\Http\Request;

class MasterlistController extends Controller
{
    /**
     * Helper function para i-check kung ang user ay Admin o Super Admin
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
        $user = $request->user();
        $roleName = strtolower($user->roleRelation->name ?? '');

        if (in_array($roleName, ['admin', 'super admin'])) {
            // Admin: Kita lahat
            $data = Masterlist::all();
        } else {
            // Citizen: Kita lang ang sariling record gamit ang 'id'
            $data = Masterlist::where('user_id', $user->id)->get();
        }

        if ($data->isEmpty()) {
            return response()->json([
                'message' => 'No records found.',
                'role_detected' => $roleName
            ], 200);
        }

        return MasterlistResource::collection($data);
    }

    /**
     * GET /api/masterlist/{id}
     */
    public function show(Request $request, $id)
    {
        $record = Masterlist::where('citizen_id', $id)->first();

        if (!$record) {
            return response()->json(['message' => 'Record not found.'], 404);
        }

        // Check kung Admin OR kung siya ang owner (Gamit ang $user->id)
        if (!$this->isAuthorizedAdmin($request) && $record->user_id != $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access.'
            ], 403);
        }

        return new MasterlistResource($record);
    }

    /**
     * POST /api/masterlist
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'scid_number' => 'required|string|unique:masterlist,scid_number', // Ginawang string at unique
            'id_status' => 'required|exists:id_issuance,status',
            'citizenship' => 'required|string|max:255',
            'barangay' => 'required|string|max:255',
            'city_municipality' => 'required|string|max:255',
            'civil_status' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'gender' => 'required|string',
            'status' => 'required|string',
            'birthdate' => 'required|date',
            'birthplace' => 'required|string',
        ]);

        $validated['user_id'] = $request->user()->id; // TAMA: $user->id
        $validated['date_submitted'] = now();

        $record = Masterlist::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Record created successfully.',
            'data' => $record
        ], 201);
    }

    /**
     * PUT /api/masterlist/{id}
     */
    public function update(Request $request, $id)
    {
        // Sa loob ng iyong update method (Approval logic)
Masterlist::create([
    // ... ibang fields
    'document_path' => $application->document_path, // Kunin mula sa application record
    // ...
]);
        // Hanapin gamit ang citizen_id
        $record = Masterlist::where('citizen_id', $id)->first();

        if (!$record) {
            return response()->json(['message' => 'Record not found.'], 404);
        }

        // Autorization check
        if (!$this->isAuthorizedAdmin($request) && $record->user_id != $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // I-update gamit ang request data
        $record->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Record updated successfully.',
            'data' => $record
        ]);
    }

    /**
     * DELETE /api/masterlist/{id}
     */
    public function destroy(Request $request, $id)
    {
        $record = Masterlist::where('citizen_id', $id)->first();

        if (!$record) {
            return response()->json(['message' => 'Record not found.'], 404);
        }

        if (!$this->isAuthorizedAdmin($request) && $record->user_id != $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized to delete this record.'
            ], 403);
        }

        $record->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Record deleted successfully.'
        ]);
    }
}