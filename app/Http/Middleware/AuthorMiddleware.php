<?php
namespace App\Http\Middleware;use Closure;use Illuminate\Http\Request;use Symfony\Component\HttpFoundation\Response;
class AuthorMiddleware{public function handle(Request $request,Closure $next):Response{$user=$request->user();if(!$user||$user->role!=='author'){return response()->json(['message'=>'Author access required.'],403);}return $next($request);}}