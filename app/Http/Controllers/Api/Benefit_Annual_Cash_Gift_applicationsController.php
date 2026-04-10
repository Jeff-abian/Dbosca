<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BenefitAnnualCashGiftApplication;
use App\Http\Resources\BenefitApplicationResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class BenefitController extends Controller
{
    public function index()
    {
        $apps = BenefitAnnualCashGiftApplication::orderBy('created_at', 'desc')->paginate(15);
        return BenefitApplicationResource::collection($apps);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'citizen_id'           => 'required|integer',
            'first_name'           => 'required|string|max:255',
            'middle_name'          => 'nullable|string|max:255',
            'last_name'            => 'required|string|max:255',
            'birth_date'           => 'required|date',
            'age'                  => 'required|integer',
            'contact_number'       => 'required|string',
            'barangay'             => 'required|string',
            'city_municipality'    => 'required|string',
            'province'             => 'required|string',
            'scid_number'          => 'required|string',
            'birth_certificate'    => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'barangay_certificate' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'photo'                => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        try {
            return DB::transaction(function () use ($request, $validated) {
                // File Uploads
                if ($request->hasFile('birth_certificate')) {
                    $validated['birth_certificate'] = $request->file('birth_certificate')->store('benefits/birth_certs', 'public');
                }
                if ($request->hasFile('barangay_certificate')) {
                    $validated['barangay_certificate'] = $request->file('barangay_certificate')->store('benefits/brgy_certs', 'public');
                }
                if ($request->hasFile('photo')) {
                    $validated['photo'] = $request->file('photo')->store('benefits/photos', 'public');
                }

                $validated['reg_status'] = 'Pending';
                $application = BenefitAnnualCashGiftApplication::create($validated);

                return new BenefitApplicationResource($application);
            });
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $app = BenefitAnnualCashGiftApplication::findOrFail($id);
        return new BenefitApplicationResource($app);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['reg_status' => 'required|in:Pending,Approved,Rejected']);
        $app = BenefitAnnualCashGiftApplication::findOrFail($id);
        $app->update(['reg_status' => $request->reg_status]);

        return response()->json(['message' => 'Status updated successfully.']);
    }
}