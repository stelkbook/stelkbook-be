<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class authController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required',
            'password' => 'required',
            'username' => 'required',
            'kode' => 'required',
            'role' => 'required|in:siswa,guru,admin'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password), // Mutator otomatis menghash password
            'username' => $request->username,
            'kode' => $request->kode,
            'role' => $request->role,
        ]);
        return response()->json(['message' => 'User created successfully'], 201);
    }
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required',
            'username' => 'required',
            'kode' => 'required',
        ]);
        $user = User::where('email',$request->email)->first();

        
        auth()->login($user);

        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ],200);

        if(!$user || !Hash::check($request->password, $user->password)){
            return response() -> json(['message' => 'Unauthorized: Incorrect credentials'],401);
        }

        if($user->username !== $request->username){
           return response() -> json(['message' => 'Unauthorized: the username is incorrect'],401);
        }
        if($user->kode !== $request->kode){
            return response() -> json(['message' => 'Unauthorized: the kode is incorrect'],401);    
        }



    }
   public function user(Request $request){
    return response()->json($request->user()); 
   }

   public function logout(Request $request){
    $request->user()->currentAccessToken()->delete();
    return response()->json(['message' => 'Logged out successfully']);
   }
}
