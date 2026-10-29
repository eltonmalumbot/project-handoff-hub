<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAllowedOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isAllowedOwner(), 403, 'Akun ini tidak diizinkan.');

        return $next($request);
    }
}
