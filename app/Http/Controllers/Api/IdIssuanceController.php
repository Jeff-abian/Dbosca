<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IdIssuance;
use App\Http\Resources\IdIssuanceResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class IdIssuanceController extends Controller
{
    public function index()
    {
        return IdIssuanceResource::collection(IdIssuance::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'citizen_id' => 'required|integer',
            'id_number'  => 'required|unique:id_issuance,id_number',
            'issued_date'=> 'required|date_format:Y-m-d',
            'status'     => 'required|string'
        ]);

        $issuance = IdIssuance::create($validated);
        return new IdIssuanceResource($issuance);
    }

    public function show($id)
    {
        $issuance = IdIssuance::findOrFail($id);
        return new IdIssuanceResource($issuance);
    }

    public function update(Request $request, $id)
    {
        $issuance = IdIssuance::findOrFail($id);
        
        $validated = $request->validate([
            'released_date' => 'nullable|date_format:Y-m-d',
            'status'        => 'sometimes|string'
        ]);

        $issuance->update($validated);
        return new IdIssuanceResource($issuance);
    }

    public function destroy($id)
    {
        $issuance = IdIssuance::findOrFail($id);
        $issuance->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}