<?php
namespace App\Controllers;

use Model;
use App\Helpers\JwtHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
// use App\Services\ArrayConversionService;
use PDOException;
// use Slim\Exception\HttpBadRequestException;
// use Slim\Exception\HttpInternalServerErrorException;

class AuthController extends HomeController
{
    protected $logger;

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    public function register(Request $request, Response $response) {
        $data = $request->getParsedBody();

        // Basic validation
        if (!isset($data['name'], $data['email'], $data['email'], $data['password'])) {
            throw new HttpBadRequestException($request, 'Missing required fields');
        }

        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        // Store user in the database (using Idiorm)
        $user = Model::factory('User')->create();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'];
        $user->password = $hashedPassword;
        $user->save();

        // Generate JWT Token
        $token = JwtHelper::generateToken($user->id);

        // Return response
        return $response->withJson(['message' => 'User registered successfully', 'token' => $token]);
    }
}
