<?php
namespace App\Helpers;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHelper {
    private static $secretKey;
    private static $issuer;
    private static $expiry;

    public static function init() {
 
        self::$secretKey = $_ENV['JWT_SECRET'];
        self::$issuer = $_ENV['JWT_ISSUER'];
        self::$expiry = $_ENV['JWT_EXPIRY'];
    }

    public static function generateToken($userId) {
        self::init();
        $payload = [
            'iss' => self::$issuer,
            'iat' => time(),
            'exp' => time() + self::$expiry,
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
