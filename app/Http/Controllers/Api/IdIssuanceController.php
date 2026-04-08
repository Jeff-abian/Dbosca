<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IdIssuance;
use App\Http\Resources\IdIssuanceResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IdIssuanceController extends Controller
{
    /**
     * Display a listing of the resource without pagination.
     */
    public function index(): AnonymousResourceCollection
    {
        // Fetching all records as requested
        $issuances = IdIssuance::with('masterlist')->get();
        
        return IdIssuanceResource::collection($issuances);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'scid_number'              => 'required|exists:masterlist,scid_number|unique:id_issuance,scid_number',
            'citizen_id'               => 'required|exists:masterlist,citizen_id',
            'id_request_type'          => 'required|in:New ID,Renewal,Lost/Damage',
            'id_modality'              => 'required|in:Walk-in,Online',
            'emergency_contact_person' => 'nullable|string|max:150',
            'emergency_contact_number' => 'nullable|string|max:20',
            'application_date'         => 'nullable|date',
            'photo_url'                => 'nullable|url|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $idIssuance = IdIssuance::create($request->all());

        return (new IdIssuanceResource($idIssuance))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(IdIssuance $idIssuance): IdIssuanceResource
    {
        return new IdIssuanceResource($idIssuance);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, IdIssuance $idIssuance): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_application_status' => 'sometimes|in:For approval,Approved,Rejected',
            'id_status'             => 'sometimes|in:pending,released',
            'rejection_remarks'     => 'nullable|string',
            'date_reviewed'         => 'nullable|date',
            'released_date'         => 'nullable|date',
            'id_expiration_date'    => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $idIssuance->update($request->all());

        return (new IdIssuanceResource($idIssuance))->response();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(IdIssuance $idIssuance): JsonResponse
    {
        $idIssuance->delete();
        
        return response()->json(null, 204);
    }
}