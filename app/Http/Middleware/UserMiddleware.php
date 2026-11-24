<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UserMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 401 Unauthorized:Jika Mengubah url menjadi /user tanpa login
        if (!Auth::check()) {
            abort(401, 'Autentikasi Diperlukan: Anda harus masuk (login) terlebih dahulu untuk mengakses area Pelanggan.');
        }

        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        // 403 Forbidden: Jika Admin mencoba mengubah url menjadi /user
        if ($user && $user->isAdmin()) {
            abort(403, 'Akses Ditolak: Administrator tidak diizinkan mengakses area khusus Pelanggan.');
        }

        return $next($request);
    }
}
