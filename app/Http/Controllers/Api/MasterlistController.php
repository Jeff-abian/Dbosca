<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Masterlist;
use App\Http\Resources\MasterlistResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MasterlistController extends Controller
{
    public function index()
    {
        // Filter by the ID of the user owning the Bearer Token
        $data = Masterlist::where('user_id', auth()->id())->get();
        return MasterlistResource::collection($data);
    }

    public function show($id)
    {
        $record = Masterlist::where('user_id', auth()->id())
                            ->where('citizen_id', $id)
                            ->firstOrFail();
                            
        return new MasterlistResource($record);
    }
    public function store(Request $request)
{
    // 1. Validation - siguraduhin na tama ang data na pinapasa
    $validated = $request->validate([
        'citizen_id' => 'required|integer|unique:masterlist,citizen_id',
        'citizenship' => 'required|string|max:255',
        'barangay' => 'required|string|max:255',
        'city_municipality' => 'required|string|max:255',
        'civil_status' => 'required|string|max:255',
        'district' => 'required|string|integer|max:255',
        'province' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'first_name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'gender' => 'required|string',
        'status' => 'required|string',
        'birthdate' => 'required|date',
        'birthplace' => 'required|string',
        // Idagdag dito ang iba pang kailangang columns...
    ]);

    // 2. Awtomatikong kunin ang user_id mula sa Bearer Token
    $validated['user_id'] = auth()->id();
    
    // 3. I-set ang date_submitted (optional kung gusto mong manual)
    $validated['date_submitted'] = now();

    // 4. I-save sa database
    $record = Masterlist::create($validated);

    return response()->json([
        'status' => 'success',
        'message' => 'Record created successfully.',
        'data' => $record
    ], 201); // 201 means Created
}
    public function destroy($id)
{
    // 1. Hanapin ang record gamit ang citizen_id
    $record = Masterlist::where('citizen_id', $id)->first();

    // 2. Kung walang nahanap na record
    if (!$record) {
        return response()->json([
            'status' => 'error',
            'message' => 'Record not found.'
        ], Response::HTTP_NOT_FOUND);
    }

    // 3. Ownership Check: Siguraduhing ang user_id ng record ay tugma sa naka-login na user
    if ($record->user_id !== auth()->id()) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized. You do not own this record.'
        ], Response::HTTP_FORBIDDEN);
    }

    // 4. Burahin ang record
    $record->delete();

    return response()->json([
        'status' => 'success',
        'message' => 'Record deleted successfully.'
    ], Response::HTTP_OK);
}
}

