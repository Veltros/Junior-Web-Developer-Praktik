<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * FUNGSI: Menampilkan halaman Kontak & Percakapan Chat Pengguna.
     * 
     * 1. Menampilkan formulir kirim pesan/pertanyaan ke admin.
     * 2. Jika user sudah login, sistem akan memuat seluruh riwayat percakapan chat miliknya
     *    beserta balasan resmi yang dikirimkan oleh Administrator.
     */
    public function index()
    {
        $userMessages = collect();
        $totalMessages = 0;
        $repliedCount = 0;

        if (auth()->check()) {
            $userMessages = Message::with('admin')
                ->where(function ($q) {
                    $q->where('user_id', auth()->id())
                      ->orWhere('email', auth()->user()->email);
                })
                ->orderBy('created_at', 'asc')
                ->get();

            $totalMessages = $userMessages->count();
            $repliedCount = $userMessages->whereNotNull('admin_reply')->where('admin_reply', '!=', '')->count();
        }

        return view('contact', compact('userMessages', 'totalMessages', 'repliedCount'));
    }
    
    /**
     * FUNGSI: Mengirimkan pesan/pertanyaan baru dari pengguna ke Admin.
     * 
     * 1. Memastikan pengguna sudah login (jika belum, diminta login dulu).
     * 2. Memeriksa agar topik dan isi pesan tidak boleh kosong (Validasi).
     * 3. Menyimpan pesan baru ke tabel `messages` dengan status awal 'Belum Dibaca'.
     * 4. Memberikan notifikasi sukses kepada user.
     */
    public function store(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login')
                ->with('warning', 'Silakan masuk ke akun Anda terlebih dahulu untuk mengirim pesan ke Administrator.');
        }

        $request->validate([
            'subject' => 'required|string|max:200',
            'message' => 'required|string',
        ], [
            'subject.required' => 'Topik / subjek obrolan wajib diisi.',
            'message.required' => 'Pesan obrolan tidak boleh kosong.',
        ]);

        /** @var \App\Models\User $user */
        $user = auth()->user();

        Message::create([
            'user_id' => $user->id,
            'name'    => $user->name,
            'email'   => $user->email,
            'subject' => $request->subject,
            'message' => $request->message,
            'status'  => 'Belum Dibaca',
        ]);

        return back()->with('success', 'Pesan chat Anda telah berhasil dikirim ke Administrator. Kami akan segera merespon!');
    }
}
