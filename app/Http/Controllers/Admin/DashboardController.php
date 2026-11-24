<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Category;
use App\Models\Order;
use App\Models\User;
use App\Models\Message;

class DashboardController extends Controller
{
    /**
     * FUNGSI: Menampilkan halaman utama Dashboard Admin (Ringkasan Statistik Toko).
     * Menghitung dan merangkum angka-angka penting untuk admin:
     * - Total buku, kategori, pesanan, dan jumlah pelanggan.
     * - Total pendapatan uang masuk dari pesanan yang valid.
     * - Jumlah pesanan yang butuh dikonfirmasi.
     * - Jumlah pesan pelanggan yang belum dibaca.
     * - Menampilkan 5 transaksi pesanan paling baru.
     */
    public function index()
    {
        $totalBooks = Book::count();
        $totalCategories = Category::count();
        $totalOrders = Order::count();
        $totalUsers = User::where('role', 'user')->count();
        $totalRevenue = Order::whereIn('order_status', ['Sedang Diproses', 'Sedang Dikirim', 'Selesai'])->sum('total_amount');
        $pendingOrders = Order::where('order_status', 'Menunggu Konfirmasi')->count();
        $unreadMessages = Message::where('status', 'Belum Dibaca')->count();

        $recentOrders = Order::with('user')->orderByDesc('id')->take(5)->get();

        return view('admin.dashboard', compact(
            'totalBooks', 'totalCategories', 'totalOrders', 'totalUsers',
            'totalRevenue', 'pendingOrders', 'unreadMessages', 'recentOrders'
        ));
    }
}
