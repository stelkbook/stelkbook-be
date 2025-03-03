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
            'newPassword' => 'required',
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

   // App\Http\Controllers\AuthController.php
public function deleteUser($id) {
    // Cari user berdasarkan ID
    $user = User::find($id);
    if (!$user) {
      return response()->json(['message' => 'User tidak ditemukan'], 404);
    }
  
    // Hapus data terkait berdasarkan role
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

  public function deleteSiswa($id)
{
    // Cari siswa berdasarkan ID
    $siswa = Siswa::find($id);
    if (!$siswa) {
        return response()->json(['message' => 'Siswa tidak ditemukan'], 404);
    }

    // Hapus user terkait
    User::where('id', $siswa->user_id)->delete();

    // Hapus siswa
    $siswa->delete();

    return response()->json(['message' => 'Siswa berhasil dihapus'], 200);
}

public function deleteGuru($id)
{
    // Cari guru berdasarkan ID
    $guru = Guru::find($id);
    if (!$guru) {
        return response()->json(['message' => 'Guru tidak ditemukan'], 404);
    }

    // Hapus user terkait
    User::where('id', $guru->user_id)->delete();

    // Hapus guru
    $guru->delete();

    return response()->json(['message' => 'Guru berhasil dihapus'], 200);
}

public function deletePerpus($id)
{
    // Cari perpus berdasarkan ID
    $perpus = Perpus::find($id);
    if (!$perpus) {
        return response()->json(['message' => 'Perpus tidak ditemukan'], 404);
    }

    // Hapus user terkait
    User::where('id', $perpus->user_id)->delete();

    // Hapus perpus
    $perpus->delete();

    return response()->json(['message' => 'Perpus berhasil dihapus'], 200);
}

    
    public function updateUser(Request $request, $id)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'username' => 'sometimes|unique:users,username,' . $id,
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes',
            'kode' => 'sometimes|unique:users,kode,' . $id,
            'gender' => 'sometimes|in:Laki-Laki,Perempuan',
            'sekolah' => 'nullable|in:SD,SMP,SMK|required_if:role,Siswa,Guru'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }
    
        // Cari user berdasarkan ID
        $user = User::find($id);
    
        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }
    
        // Update data user
        $user->username = $request->username ?? $user->username;
        $user->email = $request->email ?? $user->email;
        $user->gender = $request->gender ?? $user->gender;
        $user->sekolah = $request->sekolah ?? $user->sekolah;
    
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
    
        // Update kode dan tabel terkait jika ada perubahan kode
        if ($request->filled('kode') && $request->kode !== $user->kode) {
            $user->kode = $request->kode;
    
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
    
        // Simpan perubahan
        $user->save();
    
        // Update password di tabel terkait jika password diubah
        if ($request->filled('password')) {
            switch ($user->role) {
                case 'Siswa':
                    Siswa::where('user_id', $user->id)->update(['password' => Hash::make($request->password)]);
                    break;
                case 'Guru':
                    Guru::where('user_id', $user->id)->update(['password' => Hash::make($request->password)]);
                    break;
                case 'Perpus':
                    Perpus::where('user_id', $user->id)->update(['password' => Hash::make($request->password)]);
                    break;
            }
        }
    
        return response()->json([
            'message' => 'User berhasil diperbarui',
            'user' => $user
        ], 200);
    }
}

