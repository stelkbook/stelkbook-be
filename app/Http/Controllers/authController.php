<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Guru;
use App\Models\Siswa;
use App\Models\Perpus;
use App\Models\SdSiswa;
use App\Models\SmpSiswa;
use App\Models\SmkSiswa;
use App\Models\SdGuru;
use App\Models\SmpGuru;
use App\Models\SmkGuru;
use App\Models\Approve;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
class AuthController extends Controller
{

    private function generateAvatarName($username, $extension) {
        // Bersihkan username dari karakter khusus
        $cleanUsername = preg_replace('/[^A-Za-z0-9]/', '', $username);
        return 'avatar_' . $cleanUsername . '_' . uniqid() . '.' . $extension;
    }

    private function updateAvatarInAllTables($userId, $role, $avatarPath) {
        // Update berdasarkan role user
        switch ($role) {
            case 'Siswa':
                Siswa::where('user_id', $userId)->update(['avatar' => $avatarPath]);
                $siswa = Siswa::where('user_id', $userId)->first();
                if ($siswa) {
                    switch ($siswa->sekolah) {
                        case 'SD': SdSiswa::where('siswa_id', $siswa->id)->update(['avatar' => $avatarPath]); break;
                        case 'SMP': SmpSiswa::where('siswa_id', $siswa->id)->update(['avatar' => $avatarPath]); break;
                        case 'SMK': SmkSiswa::where('siswa_id', $siswa->id)->update(['avatar' => $avatarPath]); break;
                    }
                }
                break;
                
            case 'Guru':
                Guru::where('user_id', $userId)->update(['avatar' => $avatarPath]);
                $guru = Guru::where('user_id', $userId)->first();
                if ($guru) {
                    switch ($guru->sekolah) {
                        case 'SD': SdGuru::where('guru_id', $guru->id)->update(['avatar' => $avatarPath]); break;
                        case 'SMP': SmpGuru::where('siswa_id', $guru->id)->update(['avatar' => $avatarPath]); break;
                        case 'SMK': SmkGuru::where('siswa_id', $guru->id)->update(['avatar' => $avatarPath]); break;
                    }
                }
                break;
                
            case 'Perpus':
                Perpus::where('user_id', $userId)->update(['avatar' => $avatarPath]);
                break;
        }
    }

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
                'kelas' => 'nullable|in:I,II,III,IV,V,VI,VII,VIII,IX,X,XI,XII|required_if:role,Siswa',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif'
            ]);

            // Handle avatar upload
    $avatarPath = null;
    if ($request->hasFile('avatar')) {
        $avatarFile = $request->file('avatar');
    $avatarName = time() . '_' . $avatarFile->getClientOriginalName(); // tambahkan timestamp biar unik
    $avatarPath = $avatarFile->storeAs('avatars', $avatarName, 'public');
    }


            $user = User::create([
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'kode' => $request->kode,
                'role' => $request->role,
                'gender' => $request->gender,
                'sekolah' => in_array($request->role, ['Siswa', 'Guru']) ? $request->sekolah : null,
                'kelas' => $request->role === 'Siswa' ? $request->kelas : null,
                'avatar' => $avatarPath,
                'is_approved' => true // <-- tambahkan ini
            ]);

            if ($request->role === 'Siswa') {
                $user->kelas = $request->kelas;
                $user->save();
            }

            switch ($request->role) {
                case 'Siswa':
                    $siswa = Siswa::create([
                        'user_id' => $user->id,
                        'username' => $request->username,
                        'email' => $request->email,
                        'password' => $request->password,
                        'nis' => $request->kode,
                        'gender' => $request->gender,
                        'sekolah' => $request->sekolah,
                        'kelas' => $request->kelas,
                        'avatar' => $avatarPath
                    ]);

                    switch ($request->sekolah) {
                        case 'SD':
                            SdSiswa::create([
                                'user_id' => $user->id,
                                'siswa_id' => $siswa->id,
                                'username' => $request->username,
                                'email' => $request->email,
                                'password' => $request->password,
                                'nis' => $request->kode,
                                'gender' => $request->gender,
                                'kelas' => $request->kelas,
                                'avatar' => $avatarPath
                            ]);
                            break;
                        case 'SMP':
                            SmpSiswa::create([
                                'user_id' => $user->id,
                                'siswa_id' => $siswa->id,
                                'username' => $request->username,
                                'email' => $request->email,
                                'password' => $request->password,
                                'nis' => $request->kode,
                                'gender' => $request->gender,
                                'kelas' => $request->kelas,
                                'avatar' => $avatarPath
                            ]);
                            break;
                        case 'SMK':
                            SmkSiswa::create([
                                'user_id' => $user->id,
                                'siswa_id' => $siswa->id,
                                'username' => $request->username,
                                'email' => $request->email,
                                'password' => $request->password,
                                'nis' => $request->kode,
                                'gender' => $request->gender,
                                'kelas' => $request->kelas,
                                'avatar' => $avatarPath
                            ]);
                            break;
                    }
                    break;
                    
                case 'Guru':
                    $guru = Guru::create([
                        'user_id' => $user->id,
                        'username' => $request->username,
                        'email' => $request->email,
                        'password' => $request->password,
                        'nip' => $request->kode,
                        'gender' => $request->gender,
                        'sekolah' => $request->sekolah,
                        'avatar' => $avatarPath
                    ]);

                    switch ($request->sekolah) {
                        case 'SD':
                            SdGuru::create([
                                'user_id' => $user->id,
                                'guru_id' => $guru->id,
                                'username' => $request->username,
                                'email' => $request->email,
                                'password' => $request->password,
                                'nip' => $request->kode,
                                'gender' => $request->gender,
                                'sekolah' => $request->sekolah,
                                'avatar' => $avatarPath
                            ]);
                            break;
                        case 'SMP':
                            SmpGuru::create([
                                'user_id' => $user->id,
                                'guru_id' => $guru->id,
                                'username' => $request->username,
                                'email' => $request->email,
                                'password' => $request->password,
                                'nip' => $request->kode,
                                'gender' => $request->gender,
                                'sekolah' => $request->sekolah,
                                'avatar' => $avatarPath
                            ]);
                            break;
                        case 'SMK':
                            SmkGuru::create([
                                'user_id' => $user->id,
                                'guru_id' => $guru->id,
                                'username' => $request->username,
                                'email' => $request->email,
                                'password' => $request->password,
                                'nip' => $request->kode,
                                'gender' => $request->gender,
                                'sekolah' => $request->sekolah,
                                'avatar' => $avatarPath
                            ]);
                            break;
                    }
                    break;
                    
                case 'Perpus':
                    Perpus::create([
                        'user_id' => $user->id,
                        'username' => $request->username,
                        'email' => $request->email,
                        'password' => $request->password,
                        'nip' => $request->kode,
                        'gender' => $request->gender,
                        'avatar' => $avatarPath
                    ]);
                    break;
            }

            return response()->json([
                'message' => 'User created successfully',
                'user' => $user
            ], 201);
        }

        public function register2(Request $request)
        {
            $request->validate([
                'username' => 'required|unique:users,username',
                'email' => 'required|email|unique:users,email',
                'password' => 'required',
                'kode' => 'required|unique:users,kode',
                'role' => 'required|in:Siswa,Guru,Admin,Perpus',
                'gender' => 'required|in:Laki-Laki,Perempuan',
                'sekolah' => 'nullable|in:SD,SMP,SMK|required_if:role,Siswa,Guru',
                'kelas' => 'nullable|in:I,II,III,IV,V,VI,VII,VIII,IX,X,XI,XII|required_if:role,Siswa',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            ]);

            // Handle avatar upload
    $avatarPath = null;
    if ($request->hasFile('avatar')) {
        $avatarFile = $request->file('avatar');
    $avatarName = time() . '_' . $avatarFile->getClientOriginalName(); // tambahkan timestamp biar unik
    $avatarPath = $avatarFile->storeAs('avatars', $avatarName, 'public');
    }

    // ⛔ Jika Admin, langsung simpan ke tabel users
    if ($request->role === 'Admin') {
        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'kode' => $request->kode,
            'role' => 'Admin',
            'gender' => $request->gender,
            'sekolah' => null,
            'kelas' => null,
            'avatar' => $avatarPath,
            'is_approved' => true,
        ]);

        return response()->json([
            'message' => 'Admin berhasil didaftarkan.',
            'user' => $user,
        ], 201);
    }

             // Simpan ke tb_approve
    $approve = Approve::create([
        'username' => $request->username,
        'email' => $request->email,
        'password' => $request->password,
        'kode' => $request->kode,
        'role' => $request->role,
        'gender' => $request->gender,
        'sekolah' => in_array($request->role, ['Siswa', 'Guru']) ? $request->sekolah : null,
        'kelas' => $request->role === 'Siswa' ? $request->kelas : null,
        'avatar' => $avatarPath,
    ]);

            return response()->json([
                'message' => 'User created successfully',
                'user' => $approve
            ], 201);
        }

        public function pendingUsers()
        {
            $users = Approve::all();
            return response()->json($users);
        }
        

        public function approveUser($id)
        {
            $approve = Approve::findOrFail($id);
        
            DB::beginTransaction();
        
            try {
                // 1. Buat user utama
                $user = User::create([
                    'username' => $approve->username,
                    'email' => $approve->email,
                    'password' => Hash::make($approve->password),
                    'kode' => $approve->kode,
                    'role' => $approve->role,
                    'gender' => $approve->gender,
                    'sekolah' => $approve->sekolah,
                    'kelas' => $approve->kelas,
                    'avatar' => $approve->avatar,
                    'is_approved' => true
                ]);
        
                // 2. Simpan ke tabel sesuai rolenya
                switch ($approve->role) {
                    case 'Siswa':
                        $siswa = Siswa::create([
                            'user_id' => $user->id,
                            'username' => $user->username,
                            'email' => $user->email,
                            'password' => $approve->password,
                            'nis' => $user->kode,
                            'gender' => $user->gender,
                            'sekolah' => $user->sekolah,
                            'kelas' => $user->kelas,
                            'avatar' => $user->avatar
                        ]);
        
                        switch ($approve->sekolah) {
                            case 'SD':
                                SdSiswa::create([
                                    'user_id' => $user->id,
                                    'siswa_id' => $siswa->id,
                                    'username' => $user->username,
                                    'email' => $user->email,
                                    'password' => $approve->password,
                                    'nis' => $user->kode,
                                    'gender' => $user->gender,
                                    'kelas' => $user->kelas,
                                    'avatar' => $user->avatar
                                ]);
                                break;
                            case 'SMP':
                                SmpSiswa::create([
                                    'user_id' => $user->id,
                                    'siswa_id' => $siswa->id,
                                    'username' => $user->username,
                                    'email' => $user->email,
                                    'password' => $approve->password,
                                    'nis' => $user->kode,
                                    'gender' => $user->gender,
                                    'kelas' => $user->kelas,
                                    'avatar' => $user->avatar
                                ]);
                                break;
                            case 'SMK':
                                SmkSiswa::create([
                                    'user_id' => $user->id,
                                    'siswa_id' => $siswa->id,
                                    'username' => $user->username,
                                    'email' => $user->email,
                                    'password' => $approve->password,
                                    'nis' => $user->kode,
                                    'gender' => $user->gender,
                                    'kelas' => $user->kelas,
                                    'avatar' => $user->avatar
                                ]);
                                break;
                        }
                        break;
        
                    case 'Guru':
                        $guru = Guru::create([
                            'user_id' => $user->id,
                            'username' => $user->username,
                            'email' => $user->email,
                            'password' => $approve->password,
                            'nip' => $user->kode,
                            'gender' => $user->gender,
                            'sekolah' => $user->sekolah,
                            'avatar' => $user->avatar
                        ]);
        
                        switch ($approve->sekolah) {
                            case 'SD':
                                SdGuru::create([
                                    'user_id' => $user->id,
                                    'guru_id' => $guru->id,
                                    'username' => $user->username,
                                    'email' => $user->email,
                                    'password' => $approve->password,
                                    'nip' => $user->kode,
                                    'gender' => $user->gender,
                                    'sekolah' => $user->sekolah,
                                    'avatar' => $user->avatar
                                ]);
                                break;
                            case 'SMP':
                                SmpGuru::create([
                                    'user_id' => $user->id,
                                    'guru_id' => $guru->id,
                                    'username' => $user->username,
                                    'email' => $user->email,
                                    'password' => $approve->password,
                                    'nip' => $user->kode,
                                    'gender' => $user->gender,
                                    'sekolah' => $user->sekolah,
                                    'avatar' => $user->avatar
                                ]);
                                break;
                            case 'SMK':
                                SmkGuru::create([
                                    'user_id' => $user->id,
                                    'guru_id' => $guru->id,
                                    'username' => $user->username,
                                    'email' => $user->email,
                                    'password' => $approve->password,
                                    'nip' => $user->kode,
                                    'gender' => $user->gender,
                                    'sekolah' => $user->sekolah,
                                    'avatar' => $user->avatar
                                ]);
                                break;
                        }
                        break;
        
                    case 'Perpus':
                        Perpus::create([
                            'user_id' => $user->id,
                            'username' => $user->username,
                            'email' => $user->email,
                            'password' => $approve->password,
                            'nip' => $user->kode,
                            'gender' => $user->gender,
                            'avatar' => $user->avatar
                        ]);
                        break;
                }
        
                // 3. Hapus dari tb_approve
                $approve->delete();
        
                DB::commit();
        
                return response()->json(['message' => 'User berhasil disetujui dan dipindahkan.']);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Gagal menyetujui user.',
                    'error' => $e->getMessage()
                ], 500);
            }
        }
        
        public function rejectUser($id)
        {
            $pending = Approve::findOrFail($id);
            $pending->delete();
        
            return response()->json([
                'message' => 'User berhasil ditolak dan dihapus dari daftar approval.'
            ]);
        }
        

    

    public function getSiswa($id)
    {
        $siswa = Siswa::find($id);
        if (!$siswa) {
            return response()->json(['message' => 'Siswa not found'], 404);
        }
        return response()->json($siswa);
    }

    public function getSdSiswa($id)
{
    // Cari siswa SD berdasarkan ID
    $siswa = SdSiswa::find($id);

    if (!$siswa) {
        return response()->json(['message' => 'Siswa SD tidak ditemukan'], 404);
    }

    return response()->json($siswa);
}

public function getSmpSiswa($id)
{
    // Cari siswa SMP berdasarkan ID
    $siswa = SmpSiswa::find($id);

    if (!$siswa) {
        return response()->json(['message' => 'Siswa SMP tidak ditemukan'], 404);
    }

    return response()->json($siswa);
}

public function getSmkSiswa($id)
{
    // Cari siswa SMK berdasarkan ID
    $siswa = SmkSiswa::find($id);

    if (!$siswa) {
        return response()->json(['message' => 'Siswa SMK tidak ditemukan'], 404);
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

    public function getSdGuru($id)
{
    // Cari guru SD berdasarkan ID
    $guru = SdGuru::find($id);

    if (!$guru) {
        return response()->json(['message' => 'Guru SD tidak ditemukan'], 404);
    }

    return response()->json($guru);
}

public function getSmpGuru($id)
{
    // Cari guru SMP berdasarkan ID
    $guru = SmpGuru::find($id);

    if (!$guru) {
        return response()->json(['message' => 'Guru SMP tidak ditemukan'], 404);
    }

    return response()->json($guru);
}

public function getSmkGuru($id)
{
    // Cari guru SMK berdasarkan ID
    $guru = SmkGuru::find($id);

    if (!$guru) {
        return response()->json(['message' => 'Guru SMK tidak ditemukan'], 404);
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

    // ⛔ Cek apakah user sudah disetujui admin, kecuali kalau dia Admin
    if ($user->role !== 'Admin' && !$user->is_approved) {
        return response()->json([
            'message' => 'Akun Anda belum disetujui oleh admin.'
        ], 403);
    }

    // Reset sekolah & kelas kalau rolenya bukan siswa
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

    public function sdSiswa()
{
    // Ambil semua siswa SD
    $siswas = SdSiswa::all();

    if ($siswas->isEmpty()) {
        return response()->json(['message' => 'Tidak ada siswa SD ditemukan'], 404);
    }

    return response()->json($siswas);
}

public function smpSiswa()
{
    // Ambil semua siswa SMP
    $siswas = SmpSiswa::all();

    if ($siswas->isEmpty()) {
        return response()->json(['message' => 'Tidak ada siswa SMP ditemukan'], 404);
    }

    return response()->json($siswas);
}

public function smkSiswa()
{
    // Ambil semua siswa SMK
    $siswas = SmkSiswa::all();

    if ($siswas->isEmpty()) {
        return response()->json(['message' => 'Tidak ada siswa SMK ditemukan'], 404);
    }

    return response()->json($siswas);
}
    

public function guru()
{
   $guru = Guru::all();

   if ($guru -> isEmpty()){
    return response()->json(['message' => 'Tidak ada guru ditemukan'],404);
   }

   return response()->json($guru);
}

public function sdGuru()
{
    // Ambil semua guru SD
    $gurus = SdGuru::all();

    if ($gurus->isEmpty()) {
        return response()->json(['message' => 'Tidak ada guru SD ditemukan'], 404);
    }

    return response()->json($gurus);
}

public function smpGuru()
{
    // Ambil semua guru SMP
    $gurus = SmpGuru::all();

    if ($gurus->isEmpty()) {
        return response()->json(['message' => 'Tidak ada guru SMP ditemukan'], 404);
    }

    return response()->json($gurus);
}

public function smkGuru()
{
    // Ambil semua guru SMK
    $gurus = SmkGuru::all();

    if ($gurus->isEmpty()) {
        return response()->json(['message' => 'Tidak ada guru SMK ditemukan'], 404);
    }

    return response()->json($gurus);
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
    
        // Update password baru di tabel users
        $user->password = Hash::make($request->newPassword);
        $user->save();
    
        // Update password di tabel terkait berdasarkan role
        switch ($user->role) {
            case 'Siswa':
                // Update password di tabel siswas
                Siswa::where('user_id', $user->id)->update(['password' => $request->newPassword]);
    
                // Update password di tabel sd_siswas, smp_siswas, atau smk_siswas
                $siswa = Siswa::where('user_id', $user->id)->first();
                if ($siswa) {
                    switch ($siswa->sekolah) {
                        case 'SD':
                            SdSiswa::where('siswa_id', $siswa->id)->update(['password' => $request->newPassword]);
                            break;
                        case 'SMP':
                            SmpSiswa::where('siswa_id', $siswa->id)->update(['password' => $request->newPassword]);
                            break;
                        case 'SMK':
                            SmkSiswa::where('siswa_id', $siswa->id)->update(['password' => $request->newPassword]);
                            break;
                    }
                }
                break;
            case 'Guru':
                // Update password di tabel gurus
                Guru::where('user_id', $user->id)->update(['password' => $request->newPassword]);
                $guru = Guru::where('user_id', $user->id)->first();
                if ($guru) {
                    switch ($guru->sekolah) {
                        case 'SD':
                            SdGuru::where('guru_id', $guru->id)->update(['password' => $request->newPassword]);
                            break;
                        case 'SMP':
                            SmpGuru::where('guru_id', $guru->id)->update(['password' => $request->newPassword]);
                            break;
                        case 'SMK':
                            SmkGuru::where('guru_id', $guru->id)->update(['password' => $request->newPassword]);
                            break;
                    }
                }
                break;
            case 'Perpus':
                // Update password di tabel perpuses
                Perpus::where('user_id', $user->id)->update(['password' => $request->newPassword]);
                break;
        }
    
        return response()->json(['message' => 'Password berhasil diubah'], 200);
    }

   // App\Http\Controllers\AuthController.php
   public function deleteUser($id) {
    $user = User::find($id);
    if (!$user) {
        return response()->json(['message' => 'User tidak ditemukan'], 404);
    }

    // Delete user avatar if exists
    if ($user->avatar) {
        Storage::disk('public')->delete($user->avatar);
    }

    switch ($user->role) {
        case 'Siswa':
            $siswa = Siswa::where('user_id', $user->id)->first();
            if ($siswa) {
                // Delete siswa avatar if exists
                if ($siswa->avatar) {
                    Storage::disk('public')->delete($siswa->avatar);
                }

                // Delete school-specific siswa data and avatar
                switch ($siswa->sekolah) {
                    case 'SD':
                        $sdSiswa = SdSiswa::where('siswa_id', $siswa->id)->first();
                        if ($sdSiswa && $sdSiswa->avatar) {
                            Storage::disk('public')->delete($sdSiswa->avatar);
                        }
                        SdSiswa::where('siswa_id', $siswa->id)->delete();
                        break;
                    case 'SMP':
                        $smpSiswa = SmpSiswa::where('siswa_id', $siswa->id)->first();
                        if ($smpSiswa && $smpSiswa->avatar) {
                            Storage::disk('public')->delete($smpSiswa->avatar);
                        }
                        SmpSiswa::where('siswa_id', $siswa->id)->delete();
                        break;
                    case 'SMK':
                        $smkSiswa = SmkSiswa::where('siswa_id', $siswa->id)->first();
                        if ($smkSiswa && $smkSiswa->avatar) {
                            Storage::disk('public')->delete($smkSiswa->avatar);
                        }
                        SmkSiswa::where('siswa_id', $siswa->id)->delete();
                        break;
                }
                $siswa->delete();
            }
            break;
        case 'Guru':
            $guru = Guru::where('user_id', $user->id)->first();
            if ($guru) {
                // Delete guru avatar if exists
                if ($guru->avatar) {
                    Storage::disk('public')->delete($guru->avatar);
                }

                // Delete school-specific guru data and avatar
                switch ($guru->sekolah) {
                    case 'SD':
                        $sdGuru = SdGuru::where('guru_id', $guru->id)->first();
                        if ($sdGuru && $sdGuru->avatar) {
                            Storage::disk('public')->delete($sdGuru->avatar);
                        }
                        SdGuru::where('guru_id', $guru->id)->delete();
                        break;
                    case 'SMP':
                        $smpGuru = SmpGuru::where('guru_id', $guru->id)->first();
                        if ($smpGuru && $smpGuru->avatar) {
                            Storage::disk('public')->delete($smpGuru->avatar);
                        }
                        SmpGuru::where('guru_id', $guru->id)->delete();
                        break;
                    case 'SMK':
                        $smkGuru = SmkGuru::where('guru_id', $guru->id)->first();
                        if ($smkGuru && $smkGuru->avatar) {
                            Storage::disk('public')->delete($smkGuru->avatar);
                        }
                        SmkGuru::where('guru_id', $guru->id)->delete();
                        break;
                }
                $guru->delete();
            }
            break;
        case 'Perpus':
            $perpus = Perpus::where('user_id', $user->id)->first();
            if ($perpus && $perpus->avatar) {
                Storage::disk('public')->delete($perpus->avatar);
            }
            Perpus::where('user_id', $user->id)->delete();
            break;
    }

    $user->delete();

    return response()->json(['message' => 'User berhasil dihapus'], 200);
}

public function deleteSiswa($id) {
    $siswa = Siswa::find($id);
    if (!$siswa) {
        return response()->json(['message' => 'Siswa tidak ditemukan'], 404);
    }

    // Delete avatar from storage if exists
    if ($siswa->avatar) {
        Storage::disk('public')->delete($siswa->avatar);
    }

    // Delete school-specific data and avatar
    switch ($siswa->sekolah) {
        case 'SD':
            $sdSiswa = SdSiswa::where('siswa_id', $siswa->id)->first();
            if ($sdSiswa && $sdSiswa->avatar) {
                Storage::disk('public')->delete($sdSiswa->avatar);
            }
            SdSiswa::where('siswa_id', $siswa->id)->delete();
            break;
        case 'SMP':
            $smpSiswa = SmpSiswa::where('siswa_id', $siswa->id)->first();
            if ($smpSiswa && $smpSiswa->avatar) {
                Storage::disk('public')->delete($smpSiswa->avatar);
            }
            SmpSiswa::where('siswa_id', $siswa->id)->delete();
            break;
        case 'SMK':
            $smkSiswa = SmkSiswa::where('siswa_id', $siswa->id)->first();
            if ($smkSiswa && $smkSiswa->avatar) {
                Storage::disk('public')->delete($smkSiswa->avatar);
            }
            SmkSiswa::where('siswa_id', $siswa->id)->delete();
            break;
    }

    // Delete user and avatar
    $user = User::find($siswa->user_id);
    if ($user) {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }
        $user->delete();
    }

    $siswa->delete();

    return response()->json(['message' => 'Siswa berhasil dihapus'], 200);
}

public function deleteSdSiswa($id) {
    $siswa = SdSiswa::find($id);
    if (!$siswa) {
        return response()->json(['message' => 'Siswa SD tidak ditemukan'], 404);
    }

    // Delete avatar from storage if exists
    if ($siswa->avatar) {
        Storage::disk('public')->delete($siswa->avatar);
    }

    // Delete main siswa record and avatar
    $mainSiswa = Siswa::find($siswa->siswa_id);
    if ($mainSiswa) {
        if ($mainSiswa->avatar) {
            Storage::disk('public')->delete($mainSiswa->avatar);
        }
        $mainSiswa->delete();
    }

    // Delete user and avatar
    $user = User::find($siswa->user_id);
    if ($user) {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }
        $user->delete();
    }

    $siswa->delete();

    return response()->json(['message' => 'Siswa SD berhasil dihapus'], 200);
}

public function deleteSmpSiswa($id) {
    $siswa = SmpSiswa::find($id);
    if (!$siswa) {
        return response()->json(['message' => 'Siswa SMP tidak ditemukan'], 404);
    }

    if ($siswa->avatar) {
        Storage::disk('public')->delete($siswa->avatar);
    }

    $mainSiswa = Siswa::find($siswa->siswa_id);
    if ($mainSiswa) {
        if ($mainSiswa->avatar) {
            Storage::disk('public')->delete($mainSiswa->avatar);
        }
        $mainSiswa->delete();
    }

    $user = User::find($siswa->user_id);
    if ($user) {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }
        $user->delete();
    }

    $siswa->delete();

    return response()->json(['message' => 'Siswa SMP berhasil dihapus'], 200);
}

public function deleteSmkSiswa($id) {
    $siswa = SmkSiswa::find($id);
    if (!$siswa) {
        return response()->json(['message' => 'Siswa SMK tidak ditemukan'], 404);
    }

    if ($siswa->avatar) {
        Storage::disk('public')->delete($siswa->avatar);
    }

    $mainSiswa = Siswa::find($siswa->siswa_id);
    if ($mainSiswa) {
        if ($mainSiswa->avatar) {
            Storage::disk('public')->delete($mainSiswa->avatar);
        }
        $mainSiswa->delete();
    }

    $user = User::find($siswa->user_id);
    if ($user) {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }
        $user->delete();
    }

    $siswa->delete();

    return response()->json(['message' => 'Siswa SMK berhasil dihapus'], 200);
}

public function deleteGuru($id) {
    $guru = Guru::find($id);
    if (!$guru) {
        return response()->json(['message' => 'Guru tidak ditemukan'], 404);
    }

    // Delete guru avatar if exists
    if ($guru->avatar) {
        Storage::disk('public')->delete($guru->avatar);
    }

    // Delete school-specific guru data and avatar
    switch ($guru->sekolah) {
        case 'SD':
            $sdGuru = SdGuru::where('guru_id', $guru->id)->first();
            if ($sdGuru && $sdGuru->avatar) {
                Storage::disk('public')->delete($sdGuru->avatar);
            }
            SdGuru::where('guru_id', $guru->id)->delete();
            break;
        case 'SMP':
            $smpGuru = SmpGuru::where('guru_id', $guru->id)->first();
            if ($smpGuru && $smpGuru->avatar) {
                Storage::disk('public')->delete($smpGuru->avatar);
            }
            SmpGuru::where('guru_id', $guru->id)->delete();
            break;
        case 'SMK':
            $smkGuru = SmkGuru::where('guru_id', $guru->id)->first();
            if ($smkGuru && $smkGuru->avatar) {
                Storage::disk('public')->delete($smkGuru->avatar);
            }
            SmkGuru::where('guru_id', $guru->id)->delete();
            break;
    }

    // Delete user and avatar
    $user = User::find($guru->user_id);
    if ($user) {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }
        $user->delete();
    }

    $guru->delete();

    return response()->json(['message' => 'Guru berhasil dihapus'], 200);
}

public function deleteSdGuru($id) {
    $guru = SdGuru::find($id);
    if (!$guru) {
        return response()->json(['message' => 'Guru SD tidak ditemukan'], 404);
    }

    // Delete avatar if exists
    if ($guru->avatar) {
        Storage::disk('public')->delete($guru->avatar);
    }

    // Delete main guru record and avatar
    $mainGuru = Guru::find($guru->guru_id);
    if ($mainGuru) {
        if ($mainGuru->avatar) {
            Storage::disk('public')->delete($mainGuru->avatar);
        }
        $mainGuru->delete();
    }

    // Delete user and avatar
    $user = User::find($guru->user_id);
    if ($user) {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }
        $user->delete();
    }

    $guru->delete();

    return response()->json(['message' => 'Guru SD berhasil dihapus'], 200);
}

public function deleteSmpGuru($id) {
    $guru = SmpGuru::find($id);
    if (!$guru) {
        return response()->json(['message' => 'Guru SMP tidak ditemukan'], 404);
    }

    if ($guru->avatar) {
        Storage::disk('public')->delete($guru->avatar);
    }

    $mainGuru = Guru::find($guru->guru_id);
    if ($mainGuru) {
        if ($mainGuru->avatar) {
            Storage::disk('public')->delete($mainGuru->avatar);
        }
        $mainGuru->delete();
    }

    $user = User::find($guru->user_id);
    if ($user) {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }
        $user->delete();
    }

    $guru->delete();

    return response()->json(['message' => 'Guru SMP berhasil dihapus'], 200);
}

public function deleteSmkGuru($id) {
    $guru = SmkGuru::find($id);
    if (!$guru) {
        return response()->json(['message' => 'Guru SMK tidak ditemukan'], 404);
    }

    if ($guru->avatar) {
        Storage::disk('public')->delete($guru->avatar);
    }

    $mainGuru = Guru::find($guru->guru_id);
    if ($mainGuru) {
        if ($mainGuru->avatar) {
            Storage::disk('public')->delete($mainGuru->avatar);
        }
        $mainGuru->delete();
    }

    $user = User::find($guru->user_id);
    if ($user) {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }
        $user->delete();
    }

    $guru->delete();

    return response()->json(['message' => 'Guru SMK berhasil dihapus'], 200);
}

public function deletePerpus($id) {
    $perpus = Perpus::find($id);
    if (!$perpus) {
        return response()->json(['message' => 'Perpus tidak ditemukan'], 404);
    }

    // Delete avatar if exists
    if ($perpus->avatar) {
        Storage::disk('public')->delete($perpus->avatar);
    }

    // Delete user and avatar
    $user = User::find($perpus->user_id);
    if ($user) {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }
        $user->delete();
    }

    $perpus->delete();

    return response()->json(['message' => 'Perpus berhasil dihapus'], 200);
}

    
public function updateUser(Request $request, $id) {
    $validator = Validator::make($request->all(), [
        'username' => 'sometimes|unique:users,username,' . $id,
        'email' => 'sometimes|email|unique:users,email,' . $id,
        'password' => 'sometimes',
        'kode' => 'sometimes|unique:users,kode,' . $id,
        'gender' => 'sometimes|in:Laki-Laki,Perempuan',
        'sekolah' => 'nullable|in:SD,SMP,SMK|required_if:role,Siswa',
        'kelas' => 'nullable|in:I,II,III,IV,V,VI,VII,VIII,IX,X,XI,XII|required_if:role,Siswa',
        'avatar' => 'sometimes|image|mimes:jpeg,png,jpg,gif'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validasi gagal',
            'errors' => $validator->errors(),
        ], 422);
    }

    $user = User::find($id);
    if (!$user) {
        return response()->json(['message' => 'User tidak ditemukan'], 404);
    }

    if ($request->hasFile('avatar')) {
        // Delete old avatar if exists
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }
        
        $extension = $request->file('avatar')->getClientOriginalExtension();
        $avatarName = $this->generateAvatarName($user->username, $extension);
        $avatarPath = $request->file('avatar')->storeAs('avatars', $avatarName, 'public');
        
        // Update semua tabel terkait
        $this->updateAvatarInAllTables($user->id, $user->role, $avatarPath);
        
        $user->avatar = $avatarPath;
}

    $user->username = $request->username ?? $user->username;
    $user->email = $request->email ?? $user->email;
    $user->gender = $request->gender ?? $user->gender;
    $user->sekolah = $request->sekolah ?? $user->sekolah;
    $user->kelas = $request->kelas ?? $user->kelas;

    if ($request->filled('password')) {
        $user->password = Hash::make($request->password);
    }

    if ($request->filled('kode') && $request->kode !== $user->kode) {
        $user->kode = $request->kode;

        switch ($user->role) {
            case 'Siswa':
                $siswa = Siswa::where('user_id', $user->id)->first();
                if ($siswa) {
                    $siswa->nis = $request->kode;
                    $siswa->save();

                    switch ($siswa->sekolah) {
                        case 'SD':
                            SdSiswa::where('siswa_id', $siswa->id)->update(['nis' => $request->kode]);
                            break;
                        case 'SMP':
                            SmpSiswa::where('siswa_id', $siswa->id)->update(['nis' => $request->kode]);
                            break;
                        case 'SMK':
                            SmkSiswa::where('siswa_id', $siswa->id)->update(['nis' => $request->kode]);
                            break;
                    }
                }
                break;
            case 'Guru':
                $guru = Guru::where('user_id', $user->id)->first();
                if ($guru) {
                    $guru->nip = $request->kode;
                    $guru->save();

                    switch ($guru->sekolah) {
                        case 'SD':
                            SdGuru::where('guru_id', $guru->id)->update([
                                'nip' => $request->kode,
                                'username' => $request->username ?? $guru->username,
                                'email' => $request->email ?? $guru->email,
                                'gender' => $request->gender ?? $guru->gender
                            ]);
                            break;
                        case 'SMP':
                            SmpGuru::where('guru_id', $guru->id)->update([
                                'nip' => $request->kode,
                                'username' => $request->username ?? $guru->username,
                                'email' => $request->email ?? $guru->email,
                                'gender' => $request->gender ?? $guru->gender
                            ]);
                            break;
                        case 'SMK':
                            SmkGuru::where('guru_id', $guru->id)->update([
                                'nip' => $request->kode,
                                'username' => $request->username ?? $guru->username,
                                'email' => $request->email ?? $guru->email,
                                'gender' => $request->gender ?? $guru->gender
                            ]);
                            break;
                    }
                }
                break;
            case 'Perpus':
                Perpus::where('user_id', $user->id)->update(['nip' => $request->kode]);
                break;
        }
    }

    $user->save();

    // Update avatar in related tables
    if (isset($avatarPath)) {
        switch ($user->role) {
            case 'Siswa':
                Siswa::where('user_id', $user->id)->update(['avatar' => $avatarPath]);
                $siswa = Siswa::where('user_id', $user->id)->first();
                if ($siswa) {
                    switch ($siswa->sekolah) {
                        case 'SD':
                            SdSiswa::where('siswa_id', $siswa->id)->update(['avatar' => $avatarPath]);
                            break;
                        case 'SMP':
                            SmpSiswa::where('siswa_id', $siswa->id)->update(['avatar' => $avatarPath]);
                            break;
                        case 'SMK':
                            SmkSiswa::where('siswa_id', $siswa->id)->update(['avatar' => $avatarPath]);
                            break;
                    }
                }
                break;
            case 'Guru':
                Guru::where('user_id', $user->id)->update(['avatar' => $avatarPath]);
                $guru = Guru::where('user_id', $user->id)->first();
                if ($guru) {
                    switch ($guru->sekolah) {
                        case 'SD':
                            SdGuru::where('guru_id', $guru->id)->update(['avatar' => $avatarPath]);
                            break;
                        case 'SMP':
                            SmpGuru::where('guru_id', $guru->id)->update(['avatar' => $avatarPath]);
                            break;
                        case 'SMK':
                            SmkGuru::where('guru_id', $guru->id)->update(['avatar' => $avatarPath]);
                            break;
                    }
                }
                break;
            case 'Perpus':
                Perpus::where('user_id', $user->id)->update(['avatar' => $avatarPath]);
                break;
        }
    }

    return response()->json([
        'message' => 'User berhasil diperbarui',
        'user' => $user
    ], 200);
}

public function updateSiswa(Request $request, $id) {
    $validator = Validator::make($request->all(), [
        'username' => 'sometimes|unique:siswas,username,' . $id,
        'email' => 'sometimes|email|unique:siswas,email,' . $id,
        'password' => 'sometimes',
        'nis' => 'sometimes|unique:siswas,nis,' . $id,
        'gender' => 'sometimes|in:Laki-Laki,Perempuan',
        'sekolah' => 'sometimes|in:SD,SMP,SMK',
        'kelas' => 'sometimes|in:I,II,III,IV,V,VI,VII,VIII,IX,X,XI,XII',
        'avatar' => 'sometimes|image|mimes:jpeg,png,jpg,gif'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validasi gagal',
            'errors' => $validator->errors(),
        ], 422);
    }

    $siswa = Siswa::find($id);
    if (!$siswa) {
        return response()->json(['message' => 'Siswa tidak ditemukan'], 404);
    }

    if ($request->hasFile('avatar')) {
        // Delete old avatar if exists
        if ($siswa->avatar) {
            Storage::disk('public')->delete($siswa->avatar);
        }
        
        $extension = $request->file('avatar')->getClientOriginalExtension();
        $avatarName = $this->generateAvatarName($siswa->username, $extension);
        $avatarPath = $request->file('avatar')->storeAs('avatars', $avatarName, 'public');
        
        // Update semua tabel terkait
        $this->updateAvatarInAllTables($siswa->id, $siswa->role, $avatarPath);
        
        $siswa->avatar = $avatarPath;
}

    $siswa->username = $request->username ?? $siswa->username;
    $siswa->email = $request->email ?? $siswa->email;
    $siswa->nis = $request->nis ?? $siswa->nis;
    $siswa->gender = $request->gender ?? $siswa->gender;
    $siswa->sekolah = $request->sekolah ?? $siswa->sekolah;
    $siswa->kelas = $request->kelas ?? $siswa->kelas;

    if ($request->filled('password')) {
        $siswa->password = $request->password;
    }

    $siswa->save();

    // Update user
    $user = User::find($siswa->user_id);
    if ($user) {
        $user->username = $siswa->username;
        $user->email = $siswa->email;
        $user->kode = $siswa->nis;
        $user->gender = $siswa->gender;
        $user->sekolah = $siswa->sekolah;
        $user->kelas = $siswa->kelas;
        
        if (isset($avatarPath)) {
            $user->avatar = $avatarPath;
        }
        
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();
    }

    // Update specific school table
    $updateData = [
        'username' => $siswa->username,
        'email' => $siswa->email,
        'nis' => $siswa->nis,
        'gender' => $siswa->gender,
        'kelas' => $siswa->kelas,
        'password' => $request->filled('password') ? $request->password : $siswa->password
    ];

    if (isset($avatarPath)) {
        $updateData['avatar'] = $avatarPath;
    }

    switch ($siswa->sekolah) {
        case 'SD':
            SdSiswa::where('siswa_id', $siswa->id)->update($updateData);
            break;
        case 'SMP':
            SmpSiswa::where('siswa_id', $siswa->id)->update($updateData);
            break;
        case 'SMK':
            SmkSiswa::where('siswa_id', $siswa->id)->update($updateData);
            break;
    }

    return response()->json([
        'message' => 'Siswa berhasil diperbarui',
        'siswa' => $siswa
    ], 200);
}

public function updateGuru(Request $request, $id) {
    $validator = Validator::make($request->all(), [
        'username' => 'sometimes|unique:gurus,username,' . $id,
        'email' => 'sometimes|email|unique:gurus,email,' . $id,
        'password' => 'sometimes',
        'nip' => 'sometimes|unique:gurus,nip,' . $id,
        'gender' => 'sometimes|in:Laki-Laki,Perempuan',
        'sekolah' => 'sometimes|in:SD,SMP,SMK',
        'avatar' => 'sometimes|image|mimes:jpeg,png,jpg,gif'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validasi gagal',
            'errors' => $validator->errors(),
        ], 422);
    }

    $guru = Guru::find($id);
    if (!$guru) {
        return response()->json(['message' => 'Guru tidak ditemukan'], 404);
    }

    // Handle avatar update
    if ($request->hasFile('avatar')) {
        // Delete old avatar if exists
        if ($guru->avatar) {
            Storage::disk('public')->delete($guru->avatar);
        }
        
        $extension = $request->file('avatar')->getClientOriginalExtension();
        $avatarName = $this->generateAvatarName($guru->username, $extension);
        $avatarPath = $request->file('avatar')->storeAs('avatars', $avatarName, 'public');
        
        // Update semua tabel terkait
        $this->updateAvatarInAllTables($guru->id, $guru->role, $avatarPath);
        
        $guru->avatar = $avatarPath;
}

    $guru->username = $request->username ?? $guru->username;
    $guru->email = $request->email ?? $guru->email;
    $guru->nip = $request->nip ?? $guru->nip;
    $guru->gender = $request->gender ?? $guru->gender;
    $guru->sekolah = $request->sekolah ?? $guru->sekolah;

    if ($request->filled('password')) {
        $guru->password = $request->password;
    }

    $guru->save();

    // Update user
    $user = User::find($guru->user_id);
    if ($user) {
        $user->username = $guru->username;
        $user->email = $guru->email;
        $user->kode = $guru->nip;
        $user->gender = $guru->gender;
        $user->sekolah = $guru->sekolah;
        
        if (isset($avatarPath)) {
            $user->avatar = $avatarPath;
        }
        
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();
    }

    // Update specific school table
    $updateData = [
        'username' => $guru->username,
        'email' => $guru->email,
        'nip' => $guru->nip,
        'gender' => $guru->gender,
        'sekolah' => $guru->sekolah,
        'password' => $request->filled('password') ? $request->password : $guru->password
    ];

    if (isset($avatarPath)) {
        $updateData['avatar'] = $avatarPath;
    }

    switch ($guru->sekolah) {
        case 'SD':
            SdGuru::where('guru_id', $guru->id)->update($updateData);
            break;
        case 'SMP':
            SmpGuru::where('guru_id', $guru->id)->update($updateData);
            break;
        case 'SMK':
            SmkGuru::where('guru_id', $guru->id)->update($updateData);
            break;
    }

    return response()->json([
        'message' => 'Guru berhasil diperbarui',
        'guru' => $guru
    ], 200);
}

public function updatePerpus(Request $request, $id) {
    $validator = Validator::make($request->all(), [
        'username' => 'sometimes|unique:perpuses,username,' . $id,
        'email' => 'sometimes|email|unique:perpuses,email,' . $id,
        'password' => 'sometimes',
        'nip' => 'sometimes|unique:perpuses,nip,' . $id,
        'gender' => 'sometimes|in:Laki-Laki,Perempuan',
        'avatar' => 'sometimes|image|mimes:jpeg,png,jpg,gif'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validasi gagal',
            'errors' => $validator->errors(),
        ], 422);
    }

    $perpus = Perpus::find($id);
    if (!$perpus) {
        return response()->json(['message' => 'Perpus tidak ditemukan'], 404);
    }

    if ($request->hasFile('avatar')) {
        // Delete old avatar if exists
        if ($perpus->avatar) {
            Storage::disk('public')->delete($perpus->avatar);
        }
        
        $extension = $request->file('avatar')->getClientOriginalExtension();
        $avatarName = $this->generateAvatarName($perpus->username, $extension);
        $avatarPath = $request->file('avatar')->storeAs('avatars', $avatarName, 'public');
        
        // Update semua tabel terkait
        $this->updateAvatarInAllTables($perpus->id, $perpus->role, $avatarPath);
        
        $perpus->avatar = $avatarPath;
}

    $perpus->username = $request->username ?? $perpus->username;
    $perpus->email = $request->email ?? $perpus->email;
    $perpus->nip = $request->nip ?? $perpus->nip;
    $perpus->gender = $request->gender ?? $perpus->gender;

    if ($request->filled('password')) {
        $perpus->password = $request->password;
    }

    $perpus->save();

    // Update user
    $user = User::find($perpus->user_id);
    if ($user) {
        $user->username = $perpus->username;
        $user->email = $perpus->email;
        $user->kode = $perpus->nip;
        $user->gender = $perpus->gender;
        
        if (isset($avatarPath)) {
            $user->avatar = $avatarPath;
        }
        
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();
    }

    return response()->json([
        'message' => 'Perpus berhasil diperbarui',
        'perpus' => $perpus
    ], 200);
}


public function updateSdSiswa(Request $request, $id) {
    $validator = Validator::make($request->all(), [
        'username' => 'sometimes|unique:sd_siswas,username,' . $id,
        'email' => 'sometimes|email|unique:sd_siswas,email,' . $id,
        'password' => 'sometimes',
        'nis' => 'sometimes|unique:sd_siswas,nis,' . $id,
        'gender' => 'sometimes|in:Laki-Laki,Perempuan',
        'kelas' => 'sometimes|in:I,II,III,IV,V,VI',
        'avatar' => 'sometimes|image|mimes:jpeg,png,jpg,gif'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validasi gagal',
            'errors' => $validator->errors(),
        ], 422);
    }

    $siswa = SdSiswa::find($id);
    if (!$siswa) {
        return response()->json(['message' => 'Siswa SD tidak ditemukan'], 404);
    }

    if ($request->hasFile('avatar')) {
        // Delete old avatar if exists
        if ($siswa->avatar) {
            Storage::disk('public')->delete($siswa->avatar);
        }
        
        $extension = $request->file('avatar')->getClientOriginalExtension();
        $avatarName = $this->generateAvatarName($siswa->username, $extension);
        $avatarPath = $request->file('avatar')->storeAs('avatars', $avatarName, 'public');
        
        // Update semua tabel terkait
        $this->updateAvatarInAllTables($siswa->id, $siswa->role, $avatarPath);
        
        $siswa->avatar = $avatarPath;
}

    $siswa->username = $request->username ?? $siswa->username;
    $siswa->email = $request->email ?? $siswa->email;
    $siswa->nis = $request->nis ?? $siswa->nis;
    $siswa->gender = $request->gender ?? $siswa->gender;
    $siswa->kelas = $request->kelas ?? $siswa->kelas;

    if ($request->filled('password')) {
        $siswa->password = $request->password;
    }

    $siswa->save();

    // Update data siswa di tabel siswas
    $updateData = [
        'username' => $siswa->username,
        'email' => $siswa->email,
        'nis' => $siswa->nis,
        'gender' => $siswa->gender,
        'sekolah' => 'SD',
        'kelas' => $siswa->kelas,
        'password' => $request->filled('password') ? $request->password : $siswa->password
    ];

    if (isset($avatarPath)) {
        $updateData['avatar'] = $avatarPath;
    }

    Siswa::where('id', $siswa->siswa_id)->update($updateData);

    // Update data user terkait
    $userUpdateData = [
        'username' => $siswa->username,
        'email' => $siswa->email,
        'kode' => $siswa->nis,
        'gender' => $siswa->gender,
        'sekolah' => 'SD',
        'kelas' => $siswa->kelas
    ];

    if (isset($avatarPath)) {
        $userUpdateData['avatar'] = $avatarPath;
    }

    if ($request->filled('password')) {
        $userUpdateData['password'] = Hash::make($request->password);
    }

    User::where('id', $siswa->user_id)->update($userUpdateData);

    return response()->json([
        'message' => 'Siswa SD berhasil diperbarui',
        'siswa' => $siswa
    ], 200);
}

public function updateSmpSiswa(Request $request, $id) {
    $validator = Validator::make($request->all(), [
        'username' => 'sometimes|unique:smp_siswas,username,' . $id,
        'email' => 'sometimes|email|unique:smp_siswas,email,' . $id,
        'password' => 'sometimes',
        'nis' => 'sometimes|unique:smp_siswas,nis,' . $id,
        'gender' => 'sometimes|in:Laki-Laki,Perempuan',
        'kelas' => 'sometimes|in:VII,VIII,IX',
        'avatar' => 'sometimes|image|mimes:jpeg,png,jpg,gif'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validasi gagal',
            'errors' => $validator->errors(),
        ], 422);
    }

    $siswa = SmpSiswa::find($id);
    if (!$siswa) {
        return response()->json(['message' => 'Siswa SMP tidak ditemukan'], 404);
    }

    if ($request->hasFile('avatar')) {
        // Delete old avatar if exists
        if ($siswa->avatar) {
            Storage::disk('public')->delete($siswa->avatar);
        }
        
        $extension = $request->file('avatar')->getClientOriginalExtension();
        $avatarName = $this->generateAvatarName($siswa->username, $extension);
        $avatarPath = $request->file('avatar')->storeAs('avatars', $avatarName, 'public');
        
        // Update semua tabel terkait
        $this->updateAvatarInAllTables($siswa->id, $siswa->role, $avatarPath);
        
        $siswa->avatar = $avatarPath;
}

    $siswa->username = $request->username ?? $siswa->username;
    $siswa->email = $request->email ?? $siswa->email;
    $siswa->nis = $request->nis ?? $siswa->nis;
    $siswa->gender = $request->gender ?? $siswa->gender;
    $siswa->kelas = $request->kelas ?? $siswa->kelas;

    if ($request->filled('password')) {
        $siswa->password = $request->password;
    }

    $siswa->save();

    // Update data siswa di tabel siswas
    $updateData = [
        'username' => $siswa->username,
        'email' => $siswa->email,
        'nis' => $siswa->nis,
        'gender' => $siswa->gender,
        'sekolah' => 'SMP',
        'kelas' => $siswa->kelas,
        'password' => $request->filled('password') ? $request->password : $siswa->password
    ];

    if (isset($avatarPath)) {
        $updateData['avatar'] = $avatarPath;
    }

    Siswa::where('id', $siswa->siswa_id)->update($updateData);

    // Update data user terkait
    $userUpdateData = [
        'username' => $siswa->username,
        'email' => $siswa->email,
        'kode' => $siswa->nis,
        'gender' => $siswa->gender,
        'sekolah' => 'SMP',
        'kelas' => $siswa->kelas
    ];

    if (isset($avatarPath)) {
        $userUpdateData['avatar'] = $avatarPath;
    }

    if ($request->filled('password')) {
        $userUpdateData['password'] = Hash::make($request->password);
    }

    User::where('id', $siswa->user_id)->update($userUpdateData);

    return response()->json([
        'message' => 'Siswa SMP berhasil diperbarui',
        'siswa' => $siswa
    ], 200);
}

public function updateSmkSiswa(Request $request, $id) {
    $validator = Validator::make($request->all(), [
        'username' => 'sometimes|unique:smk_siswas,username,' . $id,
        'email' => 'sometimes|email|unique:smk_siswas,email,' . $id,
        'password' => 'sometimes',
        'nis' => 'sometimes|unique:smk_siswas,nis,' . $id,
        'gender' => 'sometimes|in:Laki-Laki,Perempuan',
        'kelas' => 'sometimes|in:X,XI,XII',
        'avatar' => 'sometimes|image|mimes:jpeg,png,jpg,gif'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validasi gagal',
            'errors' => $validator->errors(),
        ], 422);
    }

    $siswa = SmkSiswa::find($id);
    if (!$siswa) {
        return response()->json(['message' => 'Siswa SMK tidak ditemukan'], 404);
    }

    if ($request->hasFile('avatar')) {
        // Delete old avatar if exists
        if ($siswa->avatar) {
            Storage::disk('public')->delete($siswa->avatar);
        }
        
        $extension = $request->file('avatar')->getClientOriginalExtension();
        $avatarName = $this->generateAvatarName($siswa->username, $extension);
        $avatarPath = $request->file('avatar')->storeAs('avatars', $avatarName, 'public');
        
        // Update semua tabel terkait
        $this->updateAvatarInAllTables($siswa->id, $siswa->role, $avatarPath);
        
        $siswa->avatar = $avatarPath;
}

    $siswa->username = $request->username ?? $siswa->username;
    $siswa->email = $request->email ?? $siswa->email;
    $siswa->nis = $request->nis ?? $siswa->nis;
    $siswa->gender = $request->gender ?? $siswa->gender;
    $siswa->kelas = $request->kelas ?? $siswa->kelas;

    if ($request->filled('password')) {
        $siswa->password = $request->password;
    }

    $siswa->save();

    // Update data siswa di tabel siswas
    $updateData = [
        'username' => $siswa->username,
        'email' => $siswa->email,
        'nis' => $siswa->nis,
        'gender' => $siswa->gender,
        'sekolah' => 'SMK',
        'kelas' => $siswa->kelas,
        'password' => $request->filled('password') ? $request->password : $siswa->password
    ];

    if (isset($avatarPath)) {
        $updateData['avatar'] = $avatarPath;
    }

    Siswa::where('id', $siswa->siswa_id)->update($updateData);

    // Update data user terkait
    $userUpdateData = [
        'username' => $siswa->username,
        'email' => $siswa->email,
        'kode' => $siswa->nis,
        'gender' => $siswa->gender,
        'sekolah' => 'SMK',
        'kelas' => $siswa->kelas
    ];

    if (isset($avatarPath)) {
        $userUpdateData['avatar'] = $avatarPath;
    }

    if ($request->filled('password')) {
        $userUpdateData['password'] = Hash::make($request->password);
    }

    User::where('id', $siswa->user_id)->update($userUpdateData);

    return response()->json([
        'message' => 'Siswa SMK berhasil diperbarui',
        'siswa' => $siswa
    ], 200);
}

public function updateSdGuru(Request $request, $id) {
    $validator = Validator::make($request->all(), [
        'username' => 'sometimes|unique:sd_gurus,username,' . $id,
        'email' => 'sometimes|email|unique:sd_gurus,email,' . $id,
        'password' => 'sometimes',
        'nip' => 'sometimes|unique:sd_gurus,nip,' . $id,
        'gender' => 'sometimes|in:Laki-Laki,Perempuan',
        'avatar' => 'sometimes|image|mimes:jpeg,png,jpg,gif'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validasi gagal',
            'errors' => $validator->errors(),
        ], 422);
    }

    $guru = SdGuru::find($id);
    if (!$guru) {
        return response()->json(['message' => 'Guru SD tidak ditemukan'], 404);
    }

    if ($request->hasFile('avatar')) {
        // Delete old avatar if exists
        if ($guru->avatar) {
            Storage::disk('public')->delete($guru->avatar);
        }
        
        $extension = $request->file('avatar')->getClientOriginalExtension();
        $avatarName = $this->generateAvatarName($guru->username, $extension);
        $avatarPath = $request->file('avatar')->storeAs('avatars', $avatarName, 'public');
        
        // Update semua tabel terkait
        $this->updateAvatarInAllTables($guru->id, $guru->role, $avatarPath);
        
        $guru->avatar = $avatarPath;
}

    $guru->username = $request->username ?? $guru->username;
    $guru->email = $request->email ?? $guru->email;
    $guru->nip = $request->nip ?? $guru->nip;
    $guru->gender = $request->gender ?? $guru->gender;

    if ($request->filled('password')) {
        $guru->password = $request->password;
    }

    $guru->save();

    // Update data guru di tabel gurus
    $updateData = [
        'username' => $guru->username,
        'email' => $guru->email,
        'nip' => $guru->nip,
        'gender' => $guru->gender,
        'sekolah' => 'SD',
        'password' => $request->filled('password') ? $request->password : $guru->password
    ];

    if (isset($avatarPath)) {
        $updateData['avatar'] = $avatarPath;
    }

    Guru::where('id', $guru->guru_id)->update($updateData);

    // Update data user terkait
    $userUpdateData = [
        'username' => $guru->username,
        'email' => $guru->email,
        'kode' => $guru->nip,
        'gender' => $guru->gender,
        'sekolah' => 'SD'
    ];

    if (isset($avatarPath)) {
        $userUpdateData['avatar'] = $avatarPath;
    }

    if ($request->filled('password')) {
        $userUpdateData['password'] = Hash::make($request->password);
    }

    User::where('id', $guru->user_id)->update($userUpdateData);

    return response()->json([
        'message' => 'Guru SD berhasil diperbarui',
        'guru' => $guru
    ], 200);
}

public function updateSmpGuru(Request $request, $id) {
    $validator = Validator::make($request->all(), [
        'username' => 'sometimes|unique:smp_gurus,username,' . $id,
        'email' => 'sometimes|email|unique:smp_gurus,email,' . $id,
        'password' => 'sometimes',
        'nip' => 'sometimes|unique:smp_gurus,nip,' . $id,
        'gender' => 'sometimes|in:Laki-Laki,Perempuan',
        'avatar' => 'sometimes|image|mimes:jpeg,png,jpg,gif'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validasi gagal',
            'errors' => $validator->errors(),
        ], 422);
    }

    $guru = SmpGuru::find($id);
    if (!$guru) {
        return response()->json(['message' => 'Guru SMP tidak ditemukan'], 404);
    }

    if ($request->hasFile('avatar')) {
        // Delete old avatar if exists
        if ($guru->avatar) {
            Storage::disk('public')->delete($guru->avatar);
        }
        
        $extension = $request->file('avatar')->getClientOriginalExtension();
        $avatarName = $this->generateAvatarName($guru->username, $extension);
        $avatarPath = $request->file('avatar')->storeAs('avatars', $avatarName, 'public');
        
        // Update semua tabel terkait
        $this->updateAvatarInAllTables($guru->id, $guru->role, $avatarPath);
        
        $guru->avatar = $avatarPath;
}

    $guru->username = $request->username ?? $guru->username;
    $guru->email = $request->email ?? $guru->email;
    $guru->nip = $request->nip ?? $guru->nip;
    $guru->gender = $request->gender ?? $guru->gender;

    if ($request->filled('password')) {
        $guru->password = $request->password;
    }

    $guru->save();

    // Update data guru di tabel gurus
    $updateData = [
        'username' => $guru->username,
        'email' => $guru->email,
        'nip' => $guru->nip,
        'gender' => $guru->gender,
        'sekolah' => 'SMP',
        'password' => $request->filled('password') ? $request->password : $guru->password
    ];

    if (isset($avatarPath)) {
        $updateData['avatar'] = $avatarPath;
    }

    Guru::where('id', $guru->guru_id)->update($updateData);

    // Update data user terkait
    $userUpdateData = [
        'username' => $guru->username,
        'email' => $guru->email,
        'kode' => $guru->nip,
        'gender' => $guru->gender,
        'sekolah' => 'SMP'
    ];

    if (isset($avatarPath)) {
        $userUpdateData['avatar'] = $avatarPath;
    }

    if ($request->filled('password')) {
        $userUpdateData['password'] = Hash::make($request->password);
    }

    User::where('id', $guru->user_id)->update($userUpdateData);

    return response()->json([
        'message' => 'Guru SMP berhasil diperbarui',
        'guru' => $guru
    ], 200);
}

public function updateSmkGuru(Request $request, $id) {
    $validator = Validator::make($request->all(), [
        'username' => 'sometimes|unique:smk_gurus,username,' . $id,
        'email' => 'sometimes|email|unique:smk_gurus,email,' . $id,
        'password' => 'sometimes',
        'nip' => 'sometimes|unique:smk_gurus,nip,' . $id,
        'gender' => 'sometimes|in:Laki-Laki,Perempuan',
        'avatar' => 'sometimes|image|mimes:jpeg,png,jpg,gif'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validasi gagal',
            'errors' => $validator->errors(),
        ], 422);
    }

    $guru = SmkGuru::find($id);
    if (!$guru) {
        return response()->json(['message' => 'Guru SMK tidak ditemukan'], 404);
    }

    if ($request->hasFile('avatar')) {
        // Delete old avatar if exists
        if ($guru->avatar) {
            Storage::disk('public')->delete($guru->avatar);
        }
        
        $extension = $request->file('avatar')->getClientOriginalExtension();
        $avatarName = $this->generateAvatarName($guru->username, $extension);
        $avatarPath = $request->file('avatar')->storeAs('avatars', $avatarName, 'public');
        
        // Update semua tabel terkait
        $this->updateAvatarInAllTables($guru->id, $guru->role, $avatarPath);
        
        $guru->avatar = $avatarPath;
}

    $guru->username = $request->username ?? $guru->username;
    $guru->email = $request->email ?? $guru->email;
    $guru->nip = $request->nip ?? $guru->nip;
    $guru->gender = $request->gender ?? $guru->gender;

    if ($request->filled('password')) {
        $guru->password = $request->password;
    }

    $guru->save();

    // Update data guru di tabel gurus
    $updateData = [
        'username' => $guru->username,
        'email' => $guru->email,
        'nip' => $guru->nip,
        'gender' => $guru->gender,
        'sekolah' => 'SMK',
        'password' => $request->filled('password') ? $request->password : $guru->password
    ];

    if (isset($avatarPath)) {
        $updateData['avatar'] = $avatarPath;
    }

    Guru::where('id', $guru->guru_id)->update($updateData);

    // Update data user terkait
    $userUpdateData = [
        'username' => $guru->username,
        'email' => $guru->email,
        'kode' => $guru->nip,
        'gender' => $guru->gender,
        'sekolah' => 'SMK'
    ];

    if (isset($avatarPath)) {
        $userUpdateData['avatar'] = $avatarPath;
    }

    if ($request->filled('password')) {
        $userUpdateData['password'] = Hash::make($request->password);
    }

    User::where('id', $guru->user_id)->update($userUpdateData);

    return response()->json([
        'message' => 'Guru SMK berhasil diperbarui',
        'guru' => $guru
    ], 200);
}
};


