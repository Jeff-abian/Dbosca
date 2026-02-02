<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IdIssuance;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Carbon\Carbon;

class IdIssuanceController extends Controller
{
    /**
     * Ipakita lang ang ID issuances ng naka-login na user.
     */
    public function index(Request $request)
{
    // 1. Kunin ang user object mula sa token
    $user = $request->user()->load('roleRelation'); 
    
    // 2. Tukuyin ang role name (convert to lowercase para safe ang comparison)
    $roleName = strtolower($user->roleRelation->name ?? '');

    // 3. Conditional Query logic
    if (in_array($roleName, ['admin', 'super admin'])) {
        // Kapag Admin/Super Admin, ipakita ang lahat ng records
        $data = IdIssuance::all(); 
    } else {
        // Kapag Citizen, i-filter ang data gamit ang user_id na nakuha sa token
        // Siguraduhin na 'user_id' ang tawag sa column sa table mo
        $data = IdIssuance::where('user_id', $user->id)->get();
    }

    // 4. I-return ang result
    return response()->json([
        'status' => 'success',
        'role_accessed' => $roleName,
        'data' => $data
    ]);
}

    /**
     * Mag-save ng bagong ID issuance record.
     */
    public function store(Request $request)
{
    // 1. Kunin ang authenticated user
    $user = $request->user();

    // 2. Hanapin ang kaukulang record sa Masterlist para makuha ang citizen_id
    // Ginagamit natin ang user_id bilang link
    $masterlist = \App\Models\Masterlist::where('user_id', $user->id)->first();

    if (!$masterlist) {
        return response()->json([
            'status' => 'error',
            'message' => 'User record not found in Masterlist.'
        ], 404);
    }

    // 3. Validation: Inalis ang issuance_id, citizen_id, at user_id
    $validated = $request->validate([
        'id_number' => 'required|string', // Ginawang string kung may leading zeros
        'gender' => 'required|string|max:255',
        'senior_contact_number' => 'required|string', // String para sa contact numbers
        'last_name' => 'required|string|max:255',
        'first_name' => 'required|string|max:255',
        'middle_name' => 'nullable|string|max:255',
        'suffix' => 'nullable|string|max:255',
        'birthdate' => 'required', 
        'place_of_birth' => 'required|string',
        'age' => 'required|integer',
        'house_no' => 'required|string',
        'street' => 'required|string',
        'barangay' => 'required|string',
        'city_municipality' => 'required|string',
        'province' => 'required|string',
        'district' => 'required|string',
        'citizenship' => 'required|string',
        'civil_status' => 'required|string',
        'emergency_contact_person' => 'required|string',
        'contact_number' => 'required|string',
        'willing_member' => 'required|string',
        'email' => 'required|email',
        'photo_url' => 'required|string',
        'req1_url' => 'required|string',
        'req2_url' => 'required|string',
    ]);

    // 4. Conversion ng Birthdate
    try {
        $validated['birthdate'] = \Carbon\Carbon::parse($request->birthdate)->format('Y-m-d');
    } catch (\Exception $e) {
        return response()->json(['message' => 'Invalid birthdate format.'], 422);
    }

    // 5. Automatic Assignment ng IDs at Dates
    // Ang issuance_id ay hindi na isasama rito dahil AUTO-INCREMENT ito sa database
    $validated['user_id'] = $user->id; 
    $validated['citizen_id'] = $masterlist->citizen_id; // Foreign Key mula sa Masterlist
    $validated['status'] = 'Pending'; // Default status para sa bagong request
    $validated['submitted_date'] = now();

    $issuance = \App\Models\IdIssuance::create($validated);

    return response()->json([
        'status' => 'success',
        'message' => 'ID Issuance record created successfully.',
        'data' => $issuance
    ], 201);
}
    /**
     * Ipakita ang specific record (may ownership check).
     */
    public function show($id)
    {
        $record = IdIssuance::where('issuance_id', $id)
                            ->where('user_id', auth()->id())
                            ->firstOrFail();

        return response()->json(['status' => 'success', 'data' => $record]);
    }

    /**
     * Burahin ang record (may ownership check).
     */
    public function destroy($id)
    {
        $record = IdIssuance::where('issuance_id', $id)
                            ->where('user_id', auth()->id())
                            ->first();

        if (!$record) {
            return response()->json(['message' => 'Record not found or unauthorized.'], 404);
        }

        $record->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}