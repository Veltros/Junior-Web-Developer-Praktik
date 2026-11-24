<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * FUNGSI: Menampilkan halaman formulir Edit Profil untuk user/pelanggan.
     * Menampilkan data nama, email, nomor HP, dan alamat rumah user saat ini.
     * Fitur ini dikhususkan untuk user pembeli (admin diarahkan ke dashboard admin).
     */
    public function edit()
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard')
                ->with('warning', 'Akses dibatasi: Fitur Edit Profil hanya diperuntukkan bagi akun Pelanggan toko.');
        }

        return view('profile.edit', compact('user'));
    }

    /**
     * FUNGSI: Menyimpan perubahan data profil dan ganti kata sandi.
     * 1. Mengubah nama lengkap, nomor HP, dan alamat pengiriman.
     * 2. Email sengaja dikunci (tidak bisa diubah) sebagai identitas unik login.
     * 3. Jika user ingin mengganti password baru, sistem mewajibkan verifikasi password saat ini terlebih dahulu.
     */
    public function update(Request $request)
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard')
                ->with('warning', 'Akses dibatasi: Fitur Edit Profil hanya diperuntukkan bagi akun Pelanggan toko.');
        }

        $rules = [
            'name'    => 'required|string|max:100',
            'phone'   => 'nullable|string|max:25',
            'address' => 'nullable|string|max:500',
        ];

        $customMessages = [
            'name.required'             => 'Nama lengkap wajib diisi.',
            'name.max'                  => 'Nama lengkap maksimal 100 karakter.',
            'phone.max'                 => 'Nomor telepon maksimal 25 karakter.',
            'address.max'               => 'Alamat pengiriman maksimal 500 karakter.',
            'current_password.required' => 'Kata sandi saat ini wajib diisi jika ingin mengganti kata sandi.',
            'current_password.current_password' => 'Kata sandi saat ini yang Anda masukkan salah.',
            'password.min'              => 'Kata sandi baru minimal harus 8 karakter.',
            'password.confirmed'        => 'Konfirmasi kata sandi baru tidak cocok.',
        ];

        // Jika pengguna mengisi password baru
        if ($request->filled('password')) {
            $rules['current_password'] = 'required|current_password';
            $rules['password'] = 'required|min:8|confirmed';
        }

        $validated = $request->validate($rules, $customMessages);

        // Update data yang diizinkan (Email dikunci / tidak dapat diubah)
        $user->name = trim($validated['name']);
        $user->phone = !empty($validated['phone']) ? trim($validated['phone']) : null;
        $user->address = !empty($validated['address']) ? trim($validated['address']) : null;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()->route('profile.edit')->with('success', 'Profil Anda berhasil diperbarui!');
    }
}
