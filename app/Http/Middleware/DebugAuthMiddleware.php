<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DebugAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('🔍 DEBUG AUTH MIDDLEWARE', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'auth_check' => Auth::check(),
            'auth_guard' => Auth::getDefaultDriver(),
            'user_id' => Auth::id(),
            'session_id' => $request->session()->getId(),
            'has_session' => $request->hasSession(),
            'ip' => $request->ip(),
        ]);

        $response = $next($request);

        if ($response->getStatusCode() === 403) {
            Log::warning('🚫 403 FORBIDDEN DETECTED', [
                'url' => $request->fullUrl(),
                'auth_check' => Auth::check(),
                'response_content' => $response->getContent(),
                'stack_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10),
            ]);
        }

        return $response;
    }
}
