<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Guru;
use App\Models\Siswa;
use App\Models\Perpus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
            'kode' => 'required|unique:users,kode',
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

        switch ($request->role) {
            case 'Siswa':
                Siswa::create([
                    'user_id' => $user->id,
                    'username' => $request->username,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'nis' => $request->kode,
                    'gender' => $request->gender,
                    'sekolah' => $request->sekolah,
                ]);
                break;
            case 'Guru':
                Guru::create([
                    'user_id' => $user->id,
                    'username' => $request->username,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'nip' => $request->kode,
                    'gender' => $request->gender,
                    'sekolah' => $request->sekolah,
                ]);
                break;
                case 'Perpus':
                    Perpus::create([
                        'user_id' => $user->id,
                        'username' => $request->username,
                        'email' => $request->email,
                        'password' => Hash::make($request->password), // Pastikan password tetap di-hash
                        'nip' => $request->kode,
                        'gender' => $request->gender,
                    ]);
                    break;
        }

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user
        ], 201);
    }

    public function getSiswa(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'Admin' && $user->role !== 'Siswa') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $siswa = Siswa::all(); // Ambil semua data siswa untuk admin
        return response()->json($siswa);
    }
    
    public function getGuru(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'Admin' && $user->role !== 'Guru') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $guru = Guru::all(); // Ambil semua data guru untuk admin
        return response()->json($guru);
    }
    
    public function getPerpus(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'Admin' && $user->role !== 'Perpus') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $perpus = Perpus::all(); // Ambil semua data perpus untuk admin
        return response()->json($perpus);
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
    
    public function changePassword(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'oldPassword' => 'required',
            'newPassword' => 'required|min:6',
            'confirmPassword' => 'required|same:newPassword'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }
    
        // Ambil user yang sedang login
        /** @var \App\Models\User $user **/
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }
    
        // Cek apakah password lama sesuai
        if (!Hash::check($request->oldPassword, $user->password)) {
            return response()->json(['message' => 'Password lama salah'], 400);
        }
    
        // Update password baru
        $user->password = Hash::make($request->newPassword);
        $user->save();
    
        // Update password di tabel terkait berdasarkan role
        switch ($user->role) {
            case 'Siswa':
                Siswa::where('user_id', $user->id)->update(['password' => Hash::make($request->newPassword)]);
                break;
            case 'Guru':
                Guru::where('user_id', $user->id)->update(['password' => Hash::make($request->newPassword)]);
                break;
            case 'Perpus':
                Perpus::where('user_id', $user->id)->update(['password' => Hash::make($request->newPassword)]);
                break;
        }
    
        return response()->json(['message' => 'Password berhasil diubah'], 200);
    }

    public function deleteUser(Request $request)
{
    // Ambil user yang sedang login
    /** @var \App\Models\User $user **/
    $user = $request->user();

    if (!$user) {
        return response()->json(['message' => 'User tidak ditemukan'], 404);
    }

    // Hapus data di tabel terkait berdasarkan role
    switch ($user->role) {
        case 'Siswa':
            Siswa::where('user_id', $user->id)->delete();
            break;
        case 'Guru':
            Guru::where('user_id', $user->id)->delete();
            break;
        case 'Perpus':
            Perpus::where('user_id', $user->id)->delete();
            break;
    }

    // Hapus user
    $user->delete();

    return response()->json(['message' => 'User berhasil dihapus'], 200);
}

public function updateUser(Request $request)
{
    // Validasi input
    $validator = Validator::make($request->all(), [
        'username' => 'sometimes|required|unique:users,username,' . $request->user()->id,
        'email' => 'sometimes|required|email|unique:users,email,' . $request->user()->id,
        'gender' => 'sometimes|required|in:Laki-Laki,Perempuan',
        'sekolah' => 'nullable|in:SD,SMP,SMK|required_if:role,Siswa,Guru',
        'kode' => 'sometimes|required|unique:users,kode,' . $request->user()->id, // Validasi kode unik
        'password' => 'sometimes|required' // Password baru minimal 6 karakter
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validasi gagal',
            'errors' => $validator->errors(),
        ], 422);
    }

    // Ambil user yang sedang login
    /** @var \App\Models\User $user **/
    $user = $request->user();

    if (!$user) {
        return response()->json(['message' => 'User tidak ditemukan'], 404);
    }

    // Jika ada request untuk mengganti password
    if ($request->has('password')) {
        // Update password baru di tabel users
        $user->password = Hash::make($request->password);
        $user->save();
    }

    // Jika ada request untuk mengganti kode
    if ($request->has('kode')) {
        $user->kode = $request->kode;
        $user->save();

        // Update kode di tabel terkait berdasarkan role
        switch ($user->role) {
            case 'Siswa':
                Siswa::where('user_id', $user->id)->update(['nis' => $request->kode]);
                break;
            case 'Guru':
                Guru::where('user_id', $user->id)->update(['nip' => $request->kode]);
                break;
            case 'Perpus':
                Perpus::where('user_id', $user->id)->update(['nip' => $request->kode]);
                break;
        }
    }

    // Update data user lainnya
    $user->update($request->only(['username', 'email', 'gender', 'sekolah']));

    // Update data di tabel terkait berdasarkan role
    switch ($user->role) {
        case 'Siswa':
            Siswa::where('user_id', $user->id)->update($request->only(['username', 'email', 'gender', 'sekolah']));
            break;
        case 'Guru':
            Guru::where('user_id', $user->id)->update($request->only(['username', 'email', 'gender', 'sekolah']));
            break;
        case 'Perpus':
            Perpus::where('user_id', $user->id)->update($request->only(['username', 'email', 'gender']));
            break;
    }

    return response()->json([
        'message' => 'User updated successfully',
        'user' => $user
    ], 200);
}
}

