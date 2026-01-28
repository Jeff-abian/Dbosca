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
        $validated = $request->validate([
            'issuance_id' => 'required|integer',
            'citizen_id' => 'required|integer',
            'user_id' => 'required|integer',
            'id_number' => 'required|integer',
            'gender' => 'required|string|max:255',
            'senior_contact_number' => 'required|integer',
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'required|string|max:255',
            'suffix' => 'required|string|max:255',
            'birthdate' => 'required', // Tatanggap ng format na YYYY-MM-DD o July 5, 1985
            'place_of_birth' => 'required|string',
            'age' => 'required|integer',
            'house_no' => 'required|string|integer',
            'street' => 'required|string',
            'barangay' => 'required|string',
            'city_municipality' => 'required|string',
            'province' => 'required|string',
            'district' => 'required|string|integer',
            'citizenship' => 'required|string',
            'civil_status' => 'required|string',
            'emergency_contact_person' => 'required|string',
            'contact_number' => 'required|integer',
            'willing_member' => 'required|string',
            'email' => 'required|email',
            'status' => 'required|string',
            'approved_date' => 'required',
            'submitted_date' => 'required',
            'approved_at' => 'required',
            'issued_date' => 'required',
            'released_date' => 'required',
            'photo_url' => 'required',
            'req1_url' => 'required',
            'req2_url' => 'required',



        ]);

        // Awtomatikong conversion ng birthdate format para sa MySQL
        try {
            $validated['birthdate'] = Carbon::parse($request->birthdate)->format('Y-m-d');
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid birthdate format.'], 422);
        }

        // Ikabit ang user_id mula sa Bearer Token
        $validated['user_id'] = auth()->id();
        $validated['date_submitted'] = now();

        $issuance = IdIssuance::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'ID Issuance record created successfully.',
            'data' => $issuance
        ], Response::HTTP_CREATED);
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