<?php

namespace App\Http\Controllers;

class AboutController extends Controller
{
    /**
     * FUNGSI: Menampilkan halaman "Tentang Kami" (About Us).
     */
    public function index()
    {
        return view('about');
    }
}
