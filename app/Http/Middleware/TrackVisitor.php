<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackVisitor
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Jalankan request terlebih dahulu
        $response = $next($request);

        // Abaikan request dari bot/crawler yang umum jika memungkinkan
        $userAgent = $request->userAgent();
        
        // Deteksi sederhana OS dan Browser
        $os = 'Unknown';
        $browser = 'Unknown';
        
        if (preg_match('/windows/i', $userAgent)) $os = 'Windows';
        elseif (preg_match('/mac/i', $userAgent)) $os = 'macOS';
        elseif (preg_match('/linux/i', $userAgent)) $os = 'Linux';
        elseif (preg_match('/android/i', $userAgent)) $os = 'Android';
        elseif (preg_match('/iphone|ipad/i', $userAgent)) $os = 'iOS';

        if (preg_match('/chrome/i', $userAgent)) $browser = 'Chrome';
        elseif (preg_match('/firefox/i', $userAgent)) $browser = 'Firefox';
        elseif (preg_match('/safari/i', $userAgent) && !preg_match('/chrome/i', $userAgent)) $browser = 'Safari';
        elseif (preg_match('/edge/i', $userAgent)) $browser = 'Edge';

        // Cegah duplikasi hitungan: Cek apakah IP ini sudah mengunjungi path yang sama hari ini
        $ip = $request->ip();
        $path = $request->path();
        
        $alreadyVisitedToday = \App\Models\PageVisit::where('ip_address', $ip)
            ->where('path', $path)
            ->whereDate('created_at', \Carbon\Carbon::today())
            ->exists();

        if (!$alreadyVisitedToday) {
            \App\Models\PageVisit::create([
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'browser' => $browser,
                'os' => $os,
                'path' => $path,
            ]);
        }

        return $response;
    }
}
