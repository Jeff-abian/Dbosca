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
    public function index()
    {
        $data = IdIssuance::where('user_id', auth()->id())->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $data
        ], Response::HTTP_OK);
    }

    /**
     * Mag-save ng bagong ID issuance record.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'citizen_id' => 'required|integer',
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'email' => 'required|email',
            'birthdate' => 'required', // Tatanggap ng format na YYYY-MM-DD o July 5, 1985
            'status' => 'required|string',
            'citizenship' => 'required|string',
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