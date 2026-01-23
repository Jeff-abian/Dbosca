<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IdReplacement;
use Illuminate\Http\Request;
use Carbon\Carbon;

class IdReplacementController extends Controller
{
    public function index()
    {
        // Ipakita lang ang records ng authenticated user
        $data = IdReplacement::where('user_id', auth()->id())->get();
        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'citizen_id' => 'required',
            'old_id_number' => 'required|string',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'gender' => 'required',
            'birthdate' => 'required', // Format: YYYY-MM-DD
            'status' => 'required|string',
            'suffix' => 'nullable|string', // Optional field
        ]);

        // Siguraduhin ang tamang date format para sa MySQL
        try {
            $validated['birthdate'] = Carbon::parse($request->birthdate)->format('Y-m-d');
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid birthdate format'], 422);
        }

        // Awtomatikong kunin ang user_id at submitted_date
        $validated['user_id'] = auth()->id();
        $validated['submitted_date'] = now();

        // Isama ang lahat ng iba pang fields mula sa request na nasa $fillable
        $record = IdReplacement::create(array_merge($request->all(), $validated));

        return response()->json([
            'status' => 'success',
            'message' => 'Replacement request submitted.',
            'data' => $record
        ], 201);
    }

    public function show($id)
    {
        $record = IdReplacement::where('replacement_id', $id)
                                ->where('user_id', auth()->id())
                                ->firstOrFail();
        return response()->json(['status' => 'success', 'data' => $record]);
    }

    public function destroy($id)
    {
        $record = IdReplacement::where('replacement_id', $id)
                                ->where('user_id', auth()->id())
                                ->firstOrFail();
        $record->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}