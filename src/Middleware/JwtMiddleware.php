<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Slim\Psr7\Response as SlimResponse;
use Psr\Log\LoggerInterface;

class JwtMiddleware
{
    private $secret;
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->secret = $_ENV['JWT_SECRET'];
        $this->logger = $logger;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');
        $this->logger->info('Auth header received', ['header' => $authHeader]);

        if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $this->logger->warning('No valid bearer token found in header');
            $response = new SlimResponse();
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'No token provided'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }

        try {
            $token = $matches[1];
            $this->logger->info('Attempting to decode token', ['token' => $token]);
            $this->logger->info('Using secret', ['secret' => $this->secret]); // Be careful with this in production

            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
            $this->logger->info('Token decoded successfully', ['decoded' => json_encode($decoded)]);
            
            // Add user data to request attributes
            $request = $request->withAttribute('user_id', $decoded->user_id);
            
            // Handle the request
            return $handler->handle($request);

        }  catch (\UnexpectedValueException | \DomainException $e) { 
            // Catch ONLY JWT-specific errors
            $this->logger->error('Token validation failed', ['error' => $e->getMessage()]);
            
            $response = new SlimResponse();
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Invalid or expired token',
                'debug' => $e->getMessage() // Remove in production
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }
    }
}