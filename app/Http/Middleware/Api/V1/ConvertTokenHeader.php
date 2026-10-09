<?php

namespace App\Http\Middleware\Api\V1;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConvertTokenHeader
{
    /**
     * JWT only understands "Authorization: Bearer <token>".
     * This converts "Authorization: Token <token>" into that format.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization', '');

        if (str_starts_with($header, 'Token ')) {
            $request->headers->set('Authorization', 'Bearer ' . substr($header, 6));
        }

        return $next($request);
    }
}
