<?php
namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHelper {
    private static $secretKey = "your_secret_key"; // Change this!

    public static function generateToken($userId) {
        $payload = [
            'iss' => "yourdomain.com",
            'iat' => time(),
            'exp' => time() + (60 * 60), // 1 hour expiration
            'user_id' => $userId
        ];
        return JWT::encode($payload, self::$secretKey, 'HS256');
    }

    public static function verifyToken($token) {
        try {
            return JWT::decode($token, new Key(self::$secretKey, 'HS256'));
        } catch (\Exception $e) {
            return null; // Invalid token
        }
    }
}
