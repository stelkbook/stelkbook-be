<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required',
            'kode' => 'required|unique:users',
            'role' => 'required|in:Siswa,Guru,Admin,Perpus',
            'gender' => 'required|in:Laki-Laki,Perempuan',
            'sekolah' => 'nullable|in:SD,SMP,SMK|required_if:role,Siswa,Guru'
        ]);

        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password), // Menggunakan Hash
            'kode' => $request->kode,
            'role' => $request->role,
            'gender' => $request->gender,
            'sekolah' => in_array($request->role, ['Siswa', 'Guru']) ? $request->sekolah : null,
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'kode' => 'required',
            'password' => 'required',
        ]);
    
        $user = User::where('kode', $request->kode)->first();
    
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Unauthorized: Invalid credentials'], 401);
        }
    
        $token = $user->createToken('auth_token')->plainTextToken;
    
        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ], 200);
    }
    

    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
    
}

