<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 401 Unauthorized: Jika Mengubah url menjadi /admin tanpa login
        if (!Auth::check()) {
            abort(401, 'Autentikasi Diperlukan: Anda harus masuk (login) sebagai Administrator untuk mengakses panel ini.');
        }

        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        // 403 Forbidden: Jika User biasa mencoba mengubah url menjadi /admin
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Akses Ditolak: Pelanggan tidak diizinkan mengakses area Panel Administrator.');
        }

        return $next($request);
    }
}
