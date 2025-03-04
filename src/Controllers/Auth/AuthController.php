<?php
namespace App\Controllers\Auth;

use Model;
use PDOException;
use App\Helpers\JwtHelper;
use Psr\Log\LoggerInterface;
use App\Controllers\HomeController;
use Slim\Exception\HttpBadRequestException;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpInternalServerErrorException;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController extends HomeController
{
    protected $logger;
    // return $this->response($response, [
    //     'status' => 'success',
    //     'message' => 'working'
    // ]);
    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    public function register(Request $request, Response $response) {
        $this->logger->info("Registering user");
        try{
        $data = $request->getParsedBody();
        // Basic validation
        if (!isset($data['name'], $data['email'], $data['phone'], $data['password'])) {
            $this->logger->error("Missing required fields");
            throw new HttpBadRequestException($request, 'Missing required fields');
        }
        // check if password and confirm password are the same
        if ($data['password'] !== $data['password_confirmation']) {
            $this->logger->error("Password and confirm password do not match");
            throw new HttpBadRequestException($request, 'Password and confirm password do not match');
        }
        // check if user already exists
        $user = Model::factory('User')->where('email', $data['email'])->find_one();
        if ($user) {
            $this->logger->error("User already exists");
            return $this->response($response,['status' => 'error','message' => 'User already exists'],400);
        }

        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        // Store user in the database (using Idiorm)
        $user = Model::factory('User')->create();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'];
        $user->password = $hashedPassword;
        $user->role_id = '2';
        $user->save();

        // Generate JWT Token
        $token = JwtHelper::generateToken($user->id);
        if (!$token) {
            $this->logger->error("Failed to generate token");
            return $this->response($response,['status' => 'error','message' => 'Failed to generate token'],500);
        }
        $this->logger->info("User registered successfully");
        // Return response
        return $this->response($response,['status' => 'success','message' => 'User registered successfully', 'token' => $token],201);
    } catch (PDOException $e) {
        $this->logger->error("Database error occurred while registering user", ['exception' => $e]);
        throw new HttpInternalServerErrorException($request, "Database error occurred: " . $e->getMessage());
    } catch (HttpBadRequestException $e) {
        $this->logger->warning("Bad request: " . $e->getMessage());
        throw $e;
    } catch (\Exception $e) {
        $this->logger->error("Failed to store todo item", ['exception' => $e]);
        throw new HttpInternalServerErrorException($request, "An error occurred: " . $e->getMessage());
    }
    }

    public function login(Request $request, Response $response) {
        $this->logger->info("User login attempt");
        try {
            $data = $request->getParsedBody();

            // Basic validation
            if (!isset($data['email'], $data['password'])) {
                $this->logger->error("Missing login credentials");
                throw new HttpBadRequestException($request, 'Email and password are required');
            }

            // Find user by email
            $user = Model::factory('User')->where('email', $data['email'])->find_one();
            
            if (!$user) {
                $this->logger->warning("Login attempt with non-existent email", ['email' => $data['email']]);
                return $this->response($response, [
                    'status' => 'error',
                    'message' => 'Invalid credentials'
                ], 401);
            }

            // Verify password
            if (!password_verify($data['password'], $user->password)) {
                $this->logger->warning("Failed login attempt - invalid password", ['email' => $data['email']]);
                return $this->response($response, [
                    'status' => 'error',
                    'message' => 'Invalid credentials'
                ], 401);
            }

            // Generate JWT Token
            $token = JwtHelper::generateToken($user->id);
            if (!$token) {
                $this->logger->error("Failed to generate token for user", ['user_id' => $user->id]);
                return $this->response($response, [
                    'status' => 'error',
                    'message' => 'Failed to generate authentication token'
                ], 500);
            }

            $this->logger->info("User logged in successfully", ['user_id' => $user->id]);
            
            // Return user data and token
            return $this->response($response, [
                'status' => 'success',
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role_id' => $user->role_id
                    ],
                    'token' => $token
                ]
            ], 200);

        } catch (PDOException $e) {
            $this->logger->error("Database error during login", ['exception' => $e]);
            throw new HttpInternalServerErrorException($request, "Database error occurred: " . $e->getMessage());
        } catch (HttpBadRequestException $e) {
            $this->logger->warning("Bad request during login: " . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error("Unexpected error during login", ['exception' => $e]);
            throw new HttpInternalServerErrorException($request, "An error occurred: " . $e->getMessage());
        }
    }
}
