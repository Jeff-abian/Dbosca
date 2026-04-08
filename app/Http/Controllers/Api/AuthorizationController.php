<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Masterlist; // Import natin ang Masterlist para sa Reset Password logic
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str; // Para sa helper functions

class AuthorizationController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|string|max:255',
        ]);

        $user = User::create([
            'username' => $request->username,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User Created Successfully',
             'user' => $user->makeHidden(['password', 'remember_token', 'email_verified_at']),
            'token' => $token
        ], 201);
    }

     public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required',
        ]);

        $user = User::where('username', $request->username)->first();

        // 1. Check if user exists and password is correct
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'name' => ['Invalid credentials.'],
            ])->status(Response::HTTP_UNAUTHORIZED);
        }

        // 2. Revoke any previous tokens to ensure only one is active per device/session
        $user->tokens()->delete();

        // 3. Create a new token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Logged in successfully.',
            'user' => $user->makeHidden(['password', 'remember_token', 'email_verified_at']),
            'token' => $token,
        ], Response::HTTP_OK);
    }

    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ], 200);
    }

    /**
     * LOGIC ADDITION: CHANGE PASSWORD (Para kay User/Senior Citizen)
     * Ito ang magpapalit ng has_changed into 1.
     */
    public function changePassword(Request $request)
{
    // 1. Validation - Siguraduhin na may confirmation yung bagong password
    $request->validate([
        'current_password' => 'required',
        'new_password'     => 'required|string|min:8|confirmed', // 'confirmed' means kailangan ng 'new_password_confirmation' field
    ]);

    $user = Auth::user(); // Kunin ang kasalukuyang logged-in user

    // 2. Check muna kung tama ang 'current_password' na in-input (yung 'scXXXX')
    if (!Hash::check($request->current_password, $user->password)) {
        return response()->json([
            'status' => 'error',
            'message' => 'Ang kasalukuyang password ay mali.'
        ], 422);
    }

    // 3. EXECUTION: Update Password at i-set ang has_changed sa 1
    $user->update([
        'password'    => Hash::make($request->new_password),
        'has_changed' => 1, // <--- Eto ang pinaka-importante!
    ]);

    return response()->json([
        'status'  => 'success',
        'message' => 'Password updated successfully! Hidden na rin ito sa Masterlist.'
    ]);
}

    /**
     * LOGIC ADDITION: ADMIN RESET PASSWORD
     * Ire-reset ang password sa sc + 4 random numbers format.
     */
    public function adminResetPassword(Request $request, $id)
    {
        // Hanapin ang user
        $user = User::findOrFail($id);
        
        // Generate bagong temporary password: sc + 4 numbers
        $newTempPassword = 'sc' . rand(1000, 9999);

        // Update User Table
        $user->update([
            'password'    => Hash::make($newTempPassword),
            'has_changed' => 0, // Balik sa 0 para makita ulit sa Masterlist
        ]);

        // Update Masterlist Table (Para naka-sync ang plain text temp_password)
        $masterlist = Masterlist::where('user_id', $user->id)->first();
        if ($masterlist) {
            $masterlist->update([
                'temp_password' => $newTempPassword
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'User password has been reset.',
            'credentials' => [
                'username' => $user->username,
                'new_temporary_password' => $newTempPassword
            ]
        ]);
    }
}