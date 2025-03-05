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
        'sekolah' => 'nullable|in:SD,SMP,SMK|required_if:role,Siswa,Guru',
        'kelas' => 'nullable|in:I,II,III,IV,V,VI,VII,VIII,IX,X,XI,XII|required_if:role,Siswa'
    ]);

    $user = User::create([
        'username' => $request->username,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'kode' => $request->kode,
        'role' => $request->role,
        'gender' => $request->gender,
        'sekolah' => in_array($request->role, ['Siswa', 'Guru']) ? $request->sekolah : null,
        'kelas' => $request->role === 'Siswa' ? $request->kelas : null,
    ]);

    // Logika untuk mengatur sekolah berdasarkan kelas
    if ($request->role === 'Siswa') {
        $user->kelas = $request->kelas; // Ini akan memicu mutator setKelasAttribute
        $user->save();
    }

        switch ($request->role) {
            case 'Siswa':
                Siswa::create([
                    'user_id' => $user->id,
                    'username' => $request->username,
                    'email' => $request->email,
                    'password' => $request->password,
                    'nis' => $request->kode,
                    'gender' => $request->gender,
                    'sekolah' => $request->sekolah,
                    'kelas' => $request->kelas,
                ]);
                break;
            case 'Guru':
                Guru::create([
                    'user_id' => $user->id,
                    'username' => $request->username,
                    'email' => $request->email,
                    'password' => $request->password,
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
                    'password' => $request->password,
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

    public function getSiswa($id)
    {
        $siswa = Siswa::find($id);
        if (!$siswa) {
            return response()->json(['message' => 'Siswa not found'], 404);
        }
        return response()->json($siswa);
    }
    
    public function getGuru($id)
    {
        $guru = Guru::find($id);
        if (!$guru) {
            return response()->json(['message' => 'Guru not found'], 404);
        }
        return response()->json($guru);
    }
    
    public function getPerpus($id)
    {
        $perpus = Perpus::find($id);
        if (!$perpus) {
            return response()->json(['message' => 'Perpus not found'], 404);
        }
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

        if (in_array($user->role, ['Guru', 'Admin', 'Perpus'])) {
            $user->sekolah = null;
            $user->kelas = null;
            $user->save();
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

    public function siswa()
    {
        $siswa = Siswa::all(); // Mengambil semua data siswa
        
        if ($siswa->isEmpty()) {
            return response()->json(['message' => 'Tidak ada siswa ditemukan'], 404);
        }
        
        return response()->json($siswa);
    }
    

public function guru()
{
   $guru = Guru::all();

   if ($guru -> isEmpty()){
    return response()->json(['message' => 'Tidak ada guru ditemukan'],404);
   }

   return response()->json($guru);
}

public function perpus()
{
    $perpus = Perpus::all();

    if ($perpus -> isEmpty()){
     return response()->json(['message' => 'Tidak ada guru ditemukan'],404);
    }
 
    return response()->json($perpus);
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
                Siswa::where('user_id', $user->id)->update(['password' => ($request->newPassword)]);
                break;
            case 'Guru':
                Guru::where('user_id', $user->id)->update(['password' => ($request->newPassword)]);
                break;
            case 'Perpus':
                Perpus::where('user_id', $user->id)->update(['password' => ($request->newPassword)]);
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
        'sekolah' => 'nullable|in:SD,SMP,SMK|required_if:role,Siswa,Guru',
        'kelas' => 'nullable|in:I,II,III,IV,V,VI,VII,VIII,IX,X,XI,XII|required_if:role,Siswa'
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

    if ($request->filled('kelas')) {
        $user->kelas = $request->kelas; // Ini akan memicu mutator setKelasAttribute
    }

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

public function updateSiswa(Request $request, $id)
{
    // Validasi input
    $validator = Validator::make($request->all(), [
        'username' => 'sometimes|unique:siswas,username,' . $id,
        'email' => 'sometimes|email|unique:siswas,email,' . $id,
        'password' => 'sometimes',
        'nis' => 'sometimes|unique:siswas,nis,' . $id,
        'gender' => 'sometimes|in:Laki-Laki,Perempuan',
        'sekolah' => 'sometimes|in:SD,SMP,SMK',
        'kelas' => 'sometimes|in:I,II,III,IV,V,VI,VII,VIII,IX,X,XI,XII'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validasi gagal',
            'errors' => $validator->errors(),
        ], 422);
    }

    // Cari siswa berdasarkan ID
    $siswa = Siswa::find($id);

    if (!$siswa) {
        return response()->json(['message' => 'Siswa tidak ditemukan'], 404);
    }

    // Update data siswa
    $siswa->username = $request->username ?? $siswa->username;
    $siswa->email = $request->email ?? $siswa->email;
    $siswa->nis = $request->nis ?? $siswa->nis;
    $siswa->gender = $request->gender ?? $siswa->gender;
    $siswa->sekolah = $request->sekolah ?? $siswa->sekolah;
    $siswa->kelas = $request->kelas ?? $siswa->kelas;

    if ($request->filled('password')) {
        $siswa->password = $request->password;
    }

    // Simpan perubahan
    $siswa->save();

    // Update data user terkait
    $user = User::find($siswa->user_id);
    if ($user) {
        $user->username = $siswa->username;
        $user->email = $siswa->email;
        $user->kode = $siswa->nis;
        $user->gender = $siswa->gender;
        $user->sekolah = $siswa->sekolah;
        $user->kelas = $siswa->kelas;

        if ($request->filled('password')) {
            $user->password =$request->password;
        }

        $user->save();
    }

    return response()->json([
        'message' => 'Siswa berhasil diperbarui',
        'siswa' => $siswa
    ], 200);
}

public function updateGuru(Request $request, $id)
{
    // Validasi input
    $validator = Validator::make($request->all(), [
        'username' => 'sometimes|unique:gurus,username,' . $id,
        'email' => 'sometimes|email|unique:gurus,email,' . $id,
        'password' => 'sometimes',
        'nip' => 'sometimes|unique:gurus,nip,' . $id,
        'gender' => 'sometimes|in:Laki-Laki,Perempuan',
        'sekolah' => 'sometimes|in:SD,SMP,SMK'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validasi gagal',
            'errors' => $validator->errors(),
        ], 422);
    }

    // Cari guru berdasarkan ID
    $guru = Guru::find($id);

    if (!$guru) {
        return response()->json(['message' => 'Guru tidak ditemukan'], 404);
    }

    // Update data guru
    $guru->username = $request->username ?? $guru->username;
    $guru->email = $request->email ?? $guru->email;
    $guru->nip = $request->nip ?? $guru->nip;
    $guru->gender = $request->gender ?? $guru->gender;
    $guru->sekolah = $request->sekolah ?? $guru->sekolah;

    if ($request->filled('password')) {
        $guru->password = $request->password;
    }

    // Simpan perubahan
    $guru->save();

    // Update data user terkait
    $user = User::find($guru->user_id);
    if ($user) {
        $user->username = $guru->username;
        $user->email = $guru->email;
        $user->kode = $guru->nip;
        $user->gender = $guru->gender;
        $user->sekolah = $guru->sekolah;

        if ($request->filled('password')) {
            $user->password = $request->password;
        }

        $user->save();
    }

    return response()->json([
        'message' => 'Guru berhasil diperbarui',
        'guru' => $guru
    ], 200);
}

public function updatePerpus(Request $request, $id)
{
    // Validasi input
    $validator = Validator::make($request->all(), [
        'username' => 'sometimes|unique:perpuses,username,' . $id,
        'email' => 'sometimes|email|unique:perpuses,email,' . $id,
        'password' => 'sometimes',
        'nip' => 'sometimes|unique:perpuses,nip,' . $id,
        'gender' => 'sometimes|in:Laki-Laki,Perempuan'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validasi gagal',
            'errors' => $validator->errors(),
        ], 422);
    }

    // Cari data perpus berdasarkan ID
    $perpus = Perpus::find($id); // Pastikan modelnya mengacu ke tabel `pepuses`
    if (!$perpus) {
        return response()->json(['message' => 'Perpus tidak ditemukan'], 404);
    }

    // Update data perpus
    $perpus->username = $request->username ?? $perpus->username;
    $perpus->email = $request->email ?? $perpus->email;
    $perpus->nip = $request->nip ?? $perpus->nip;
    $perpus->gender = $request->gender ?? $perpus->gender;

    if ($request->filled('password')) {
        $perpus->password = $request->password;
    }

    // Simpan perubahan
    $perpus->save();

    // Update data user terkait
    $user = User::find($perpus->user_id);
    if ($user) {
        $user->username = $perpus->username;
        $user->email = $perpus->email;
        $user->kode = $perpus->nip;
        $user->gender = $perpus->gender;

        if ($request->filled('password')) {
            $user->password = $request->password;
        }

        $user->save();
    }

    return response()->json([
        'message' => 'Perpus berhasil diperbarui',
        'perpus' => $perpus
    ], 200);
}
}

