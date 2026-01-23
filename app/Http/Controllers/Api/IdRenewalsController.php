<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class IdRenewals extends Controller
{
    /**
     * POST /api/id-renewals
     * Bukas para sa submission ng renewal application.
     */
    public function store(Request $request)
{
    $validated = $request->validate([
        'citizen_id' => 'required|integer|exists:masterlist,citizen_id',
        'old_id_number' => 'required|string',
        'contact_number' => 'required|string',
        // ... iba pang validation
    ]);

    // Kukunin ang user_id mula sa Bearer Token
    $validated['user_id'] = $request->user()->user_id; 
    $validated['status'] = 'Pending';
    $validated['submitted_date'] = now();

    // Isama ang lahat ng data at i-save
    $renewal = IdRenewal::create(array_merge($request->all(), $validated));

    return response()->json([
        'status' => 'success',
        'message' => 'Renewal request secured and submitted.',
        'data' => $renewal
    ], 201);
}

    /**
     * GET /api/id-renewals
     * Admin o User view depende sa credentials.
     */
    public function index(Request $request)
    {
        if ($request->user() && $request->user()->role === 'admin') {
            return response()->json(IdRenewal::all());
        }

        return response()->json(
            IdRenewal::where('user_id', auth()->id())->get()
        );
    }
}
