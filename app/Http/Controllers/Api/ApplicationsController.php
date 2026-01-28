<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\User;
use App\Models\Masterlist;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str; // <--- 1. ILAGAY DITO SA TAAS

class ApplicationsController extends Controller
{
    /**
     * GET /api/applications
     * Restricted: Admin only (Dapat gamitan ng middleware sa routes)
     */
 public function index(Request $request)
{
    // 1. I-load ang roleRelation
    $user = $request->user()->load('roleRelation');
    $roleName = strtolower(optional($user->roleRelation)->name);

    // 2. Payagan kung ang role ay 'admin' O 'super admin'
    $allowedRoles = ['admin', 'super admin'];

    if (!$user || !in_array($roleName, $allowedRoles)) {
        return response()->json([
            'status' => false,
            'message' => 'Forbidden: Only Admins and Super Admins can view this data.',
            'your_role' => $roleName ?? 'None'
        ], 403);
    }

    // 3. Ipagpatuloy ang query (Pagination)
    $status = $request->query('status', 'All');
    $query = Application::query();

    if ($status !== 'All') {
        $query->where('status', $status);
    }

    $applications = $query->orderBy('date_submitted', 'desc')->paginate(15);

    return response()->json([
        'status' => true,
        'viewing_status' => $status,
        'data' => $applications
    ]);
}
    /**
     * POST /api/applications
     * PUBLIC: Kahit sino pwedeng mag-register (No Bearer Token Required)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'suffix'     => 'nullable|string|max:10',
            'email' => 'required|email|max:255',
            'contact_number' => 'required|string|max:20',
            'citizenship'=> 'required|string|max:255',
            'house_no'=> 'required|string|max:255',
            'street' => 'required|string|max:255',
            'barangay' => 'required|string|max:255',
            'city_municipality' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'age' => 'required|integer',
            'gender' => 'required|string',
            'civil_status' => 'required|string',
            'birthdate' => 'required|date',
            'birthplace' => 'required|string',
            'living_arrangement' => 'required|string',
            'is_pensioner' => 'required|boolean',
            'pension_source' => 'nullable|string',
            'pension_amount' => 'nullable|string',
            'has_illness' => 'required|boolean',
            'illness_details' => 'nullable|string',
            'document_url' => 'required|string',
            'application_type' => 'required|string',
            'user_id' => 'nullable|integer', // Optional na lang ito dahil public registration ito
        ]);

        // Default settings para sa bagong registration
        $validated['status'] = 'Pending';
        $validated['date_submitted'] = now();

        $application = Application::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Application submitted successfully.',
        ], Response::HTTP_CREATED);
    }

    /**
     * GET /api/applications/{application}
     * Restricted: Admin only
     */
    public function show(Request $request, Application $application)
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
        }

        return response()->json(['status' => 'success', 'data' => $application], Response::HTTP_OK);
    }

    /**
     * PUT /api/applications/{application}
     * Restricted: Admin only
     */
    public function update(Request $request, Application $application)
{
    // ... (Validation at Admin check sa taas)

    $validated = $request->validate([
        'status' => 'required|in:Approved,Disapproved,Pending',
        'reason_for_disapproval' => 'required_if:status,Disapproved|string|nullable',
    ]);

    try {
        DB::transaction(function () use ($request, $application, $validated) {
            
            // 1. I-update muna ang status ng Application
            $application->update([
                'status' => $validated['status'],
                'reviewed_by' => $request->user()->name,
                'date_reviewed' => now(),
            ]);

            if ($validated['status'] === 'Approved') {
                $tempPassword = 'User' . now()->year . '!'; 
                $username = Str::slug($application->first_name . $application->last_name, '.') . '.' . rand(100, 999);

                // 2. Gagawa ng account sa Users table
                $newUser = User::create([
                    'name'     => $application->first_name . ' ' . $application->last_name,
                    'email'    => $application->email,
                    'username' => $username,
                    'password' => Hash::make($tempPassword),
                    'role'     => 3, 
                ]);

                // ⚠️ PAGBABAGO: Gamitin ang ->id (base sa screenshot mo)
                $newUserId = $newUser->id; 

                if (!$newUserId) {
                    throw new \Exception("Failed to retrieve new User ID. Please check if 'id' is the primary key in User model.");
                }

                // 3. Ililipat ang data sa Masterlist table
                Masterlist::create([
                    'user_id'           => $newUserId, // Ikinabit natin ang 'id' ng User dito
                    'citizen_id'        => $application->citizen_id,
                    'first_name'        => $application->first_name,
                    'last_name'         => $application->last_name,
                    'email'             => $application->email,
                    'barangay'          => $application->barangay,
                    'city_municipality' => $application->city_municipality,
                    'province'          => $application->province,
                    'district'          => $application->district,
                    'birthdate'         => $application->birthdate,
                    'gender'            => $application->gender,
                    'civil_status'      => $application->civil_status,
                    'citizenship'       => $application->citizenship,
                    'status'            => 'Active',
                    'date_submitted'    => now(),
                ]);
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Application updated. User account created and data migrated.'
        ]);

    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed: ' . $e->getMessage()], 500);
    }
}
    /**
     * DELETE /api/applications/{application}
     * Restricted: Admin only
     */
   public function destroy(Request $request, Application $application)
{
    // 1. I-load ang roleRelation ng user na nag-de-delete
    $user = $request->user()->load('roleRelation');
    $roleName = strtolower(optional($user->roleRelation)->name);

    // 2. Define ang roles na pwedeng mag-delete
    $allowedRoles = ['admin', 'super admin'];

    if (!$user || !in_array($roleName, $allowedRoles)) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized. Only Admins and Super Admins can delete records.',
            'your_role' => $roleName ?? 'None'
        ], 403);
    }

    // 3. Isagawa ang pag-delete
    $application->delete();

    return response()->json([
        'status' => 'success',
        'message' => 'Application record has been successfully deleted.'
    ], 200);
}
}