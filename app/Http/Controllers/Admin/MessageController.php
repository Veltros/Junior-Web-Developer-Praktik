<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * FUNGSI: Menampilkan halaman Kelola Pesan / Chat Pengguna di Admin.
    
     * 1. Menampilkan pesan masuk dari pengguna/pelanggan.
     * 2. Mendukung filter tab: 'Semua Pesan', 'Menunggu Balasan', dan 'Sudah Dibalas'.
     * 3. Mendukung pencarian berdasarkan nama, email, subjek, atau isi percakapan.
     */
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'all');
        $search = $request->query('q', '');

        $query = Message::with(['user', 'admin']);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if ($filter === 'pending') {
            $query->where(function ($q) {
                $q->whereNull('admin_reply')->orWhere('admin_reply', '');
            });
        } elseif ($filter === 'replied') {
            $query->whereNotNull('admin_reply')->where('admin_reply', '!=', '');
        } elseif ($filter === 'unread') {
            $query->where('status', 'Belum Dibaca');
        }

        $messages = $query->orderByDesc('id')->paginate(15)->withQueryString();

        $totalAll = Message::count();
        $totalPending = Message::whereNull('admin_reply')->orWhere('admin_reply', '')->count();
        $totalReplied = Message::whereNotNull('admin_reply')->where('admin_reply', '!=', '')->count();
        $totalUnread = Message::where('status', 'Belum Dibaca')->count();

        return view('admin.messages.index', compact(
            'messages',
            'filter',
            'search',
            'totalAll',
            'totalPending',
            'totalReplied',
            'totalUnread'
        ));
    }

    /**
     * FUNGSI: Mengirimkan balasan dari admin ke pesan/chat pengguna.
     * Menyimpan teks jawaban admin, mencatat waktu balas, mencatat ID admin yang membalas,
     * dan otomatis mengubah status pesan menjadi 'Sudah Dibaca'.
     * Balasan ini langsung bisa dibaca user di halaman chat kontak mereka.
     */
    public function reply(Request $request, Message $message)
    {
        $request->validate([
            'admin_reply' => 'required|string',
        ]);

        $message->update([
            'admin_reply' => $request->admin_reply,
            'replied_at'  => now(),
            'admin_id'    => auth()->id(),
            'status'      => 'Sudah Dibaca',
        ]);

        return back()->with('success', "Balasan resmi berhasil dikirimkan kepada '{$message->name}'. Pengguna dapat langsung melihatnya di antarmuka chat.");
    }

    /**
     * FUNGSI: Menandai pesan sebagai sudah dibaca tanpa membalas.
     */
    public function markAsRead(Message $message)
    {
        $message->update(['status' => 'Sudah Dibaca']);
        return back()->with('success', "Pesan dari '{$message->name}' telah ditandai sebagai sudah dibaca.");
    }

    /**
     * FUNGSI: Menghapus sebuah riwayat pesan dari sistem.
     */
    public function destroy(Message $message)
    {
        $name = $message->name;
        $message->delete();
        return back()->with('success', "Pesan percakapan dari '{$name}' berhasil dihapus.");
    }
}
