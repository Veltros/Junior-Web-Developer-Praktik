<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * FUNGSI: Menampilkan daftar seluruh Pengguna / Akun Terdaftar.
     * 1. Menampilkan nama user, email, nomor HP, role (Admin/User), dan total jumlah pesanannya.
     * 2. Dilengkapi fitur pencarian berdasarkan nama, email, atau nomor HP.
     * 3. Menampilkan 5 pengguna per halaman.
     */
    public function index(Request $request)
    {
        $search = trim($request->get('q', ''));

        $query = User::withCount('orders');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('id')->paginate(5)->withQueryString();

        return view('admin.users.index', compact('users', 'search'));
    }
}
