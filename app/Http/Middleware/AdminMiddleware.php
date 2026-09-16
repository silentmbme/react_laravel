<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || !$user->hasPermission('admin.access')) {
            return response()->json(['message' => 'Administrator access required.'], 403);
        }
        return $next($request);
    }
}
