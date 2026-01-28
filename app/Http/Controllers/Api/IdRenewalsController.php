<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\IdRenewal;


class IdRenewalsController extends Controller
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
        // 1. Kunin ang user object at i-load ang roleRelation
        $user = $request->user()->load('roleRelation');
        
        // 2. Kunin ang pangalan ng role (e.g., 'admin', 'citizen')
        $roleName = strtolower($user->roleRelation->name ?? '');

        // 3. Logic para sa Admin vs Citizen
        if (in_array($roleName, ['admin', 'super admin'])) {
            // Kapag Admin o Super Admin, makikita ang lahat ng renewal requests
            $data = IdRenewal::all();
        } else {
            // Kapag Citizen, makikita lang ang sariling renewal requests gamit ang user_id
            $data = IdRenewal::where('user_id', $user->id)->get();
        }

        // 4. I-return ang response
        return response()->json([
            'status' => 'success',
            'role_detected' => $roleName,
            'data' => $data
        ]);
    }
}
