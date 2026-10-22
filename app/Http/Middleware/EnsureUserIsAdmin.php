<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Enforce admin access on the server even if an admin page is hidden in the UI.
        if ($request->user()?->isAdmin() !== true) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
