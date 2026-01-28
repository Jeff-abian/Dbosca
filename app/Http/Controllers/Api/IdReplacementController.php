<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IdReplacement;
use Illuminate\Http\Request;
use Carbon\Carbon;

class IdReplacementController extends Controller
{
    public function index(Request $request)
    {
        // 1. Kunin ang user at ang kanyang role name
        $user = $request->user()->load('roleRelation');
        $roleName = strtolower($user->roleRelation->name ?? '');

        // 2. Logic para sa Admin vs Citizen
        if (in_array($roleName, ['admin', 'super admin'])) {
            // Kita lahat ang records para sa Admin/Super Admin
            $data = IdReplacement::all();
        } else {
            // Kita lang ang sariling records para sa Citizen
            $data = IdReplacement::where('user_id', $user->id)->get();
        }

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
            'suffix' => 'nullable|string', 
        ]);

        try {
            $validated['birthdate'] = Carbon::parse($request->birthdate)->format('Y-m-d');
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid birthdate format'], 422);
        }

        // Awtomatikong kinukuha ang ID ng kahit sinong role ang nag-fill up
        $validated['user_id'] = auth()->id();
        $validated['submitted_date'] = now();

        $record = IdReplacement::create(array_merge($request->all(), $validated));

        return response()->json([
            'status' => 'success',
            'message' => 'Replacement request submitted successfully.',
            'data' => $record
        ], 201);
    }

    public function show($id)
    {
        $user = auth()->user()->load('roleRelation');
        $roleName = strtolower($user->roleRelation->name ?? '');

        // Hanapin ang record
        $query = IdReplacement::where('replacement_id', $id);

        // Kung hindi admin, dapat sa kanya ang record na tinitingnan niya
        if (!in_array($roleName, ['admin', 'super admin'])) {
            $query->where('user_id', $user->id);
        }

        $record = $query->firstOrFail();

        return response()->json(['status' => 'success', 'data' => $record]);
    }

    public function destroy($id)
    {
        // Ang pag-delete ay hinigpitan natin: sariling record lang ang pwedeng burahin
        // maliban na lang kung gusto mong pati Admin ay pwedeng mag-delete ng kahit kanino.
        $record = IdReplacement::where('replacement_id', $id)
                                ->where('user_id', auth()->id())
                                ->firstOrFail();
        $record->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}