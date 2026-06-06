<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cegah browser meng-cache response (dipakai untuk halaman /login).
 *
 * Tanpa ini, browser bisa menyajikan halaman login lama dari cache/bfcache yang
 * memuat CSRF token kedaluwarsa setelah logout — menyebabkan error 419 pada
 * submit login pertama (terlihat seperti "refresh, belum login") dan baru
 * berhasil pada percobaan kedua. Dengan no-store, halaman login selalu di-fetch
 * ulang sehingga token CSRF selalu segar.
 */
class NoStoreResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        return $response
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }
}
