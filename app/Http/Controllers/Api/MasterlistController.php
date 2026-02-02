<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Masterlist;
use App\Http\Resources\MasterlistResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
     * Makikita ng Admin/Super Admin ang lahat. Ang User ay sa kanya lang.
     */
    public function index(Request $request)
{
    $user = $request->user();
    
    // Kunin ang role name para mas accurate ang check
    $roleName = strtolower($user->roleRelation->name ?? '');

    if (in_array($roleName, ['admin', 'super admin'])) {
        // Admin: Kita lahat
        $data = Masterlist::all();
    } else {
        // Citizen: Kita lang ang sariling record
        // Siguraduhin na 'user_id' ang column name sa masterlist table mo
        $data = Masterlist::where('user_id', $user->id)->get();
    }

    if ($data->isEmpty()) {
        return response()->json([
            'message' => 'No records found for this user.',
            'debug_user_id' => $user->id,
            'role_detected' => $roleName
        ], 200);
    }

    return MasterlistResource::collection($data);
}
    /**
     * GET /api/masterlist/{id}
     * Detail view na may permission check.
     */
    public function show(Request $request, $id)
    {
        // Hanapin ang record gamit ang citizen_id (o primary key mo)
        $record = Masterlist::where('citizen_id', $id)->first();

        if (!$record) {
            return response()->json(['message' => 'Record not found.'], 404);
        }

        // Check kung Admin/Super Admin OR kung siya ang owner
        if (!$this->isAuthorizedAdmin($request) && $record->user_id !== $request->user()->user_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access.'
            ], 403);
        }

        return new MasterlistResource($record);
    }

    /**
     * POST /api/masterlist
     * Pag-create ng bagong record sa Masterlist.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'scid_number' => 'required|integer',
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

        $validated['user_id'] = $request->user()->user_id;
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
     * Pag-update ng data (Admin/Super Admin only o Owner).
     */
    public function update(Request $request, $id)
    {
        $record = Masterlist::where('citizen_id', $id)->first();

        if (!$record) {
            return response()->json(['message' => 'Record not found.'], 404);
        }

        if (!$this->isAuthorizedAdmin($request) && $record->user_id !== $request->user()->user_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $record->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Record updated successfully.',
            'data' => $record
        ]);
    }

    /**
     * DELETE /api/masterlist/{id}
     * Admin/Super Admin can delete anything. User can only delete theirs.
     */
    public function destroy(Request $request, $id)
    {
        $record = Masterlist::where('citizen_id', $id)->first();

        if (!$record) {
            return response()->json(['message' => 'Record not found.'], 404);
        }

        if (!$this->isAuthorizedAdmin($request) && $record->user_id !== $request->user()->user_id) {
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