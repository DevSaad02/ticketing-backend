<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Response as SlimResponse;

class CorsMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        // Handle preflight (OPTIONS) requests **BEFORE** passing to next handler
        if ($request->getMethod() === 'OPTIONS') {
            $response = new SlimResponse(200); // Empty response for OPTIONS
        } else {
            $response = $handler->handle($request);
        }

        // Add CORS headers to the response
        return $response
            ->withHeader('Access-Control-Allow-Origin', 'http://localhost:3000')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
            ->withHeader('Access-Control-Allow-Credentials', 'true');
    }
}
