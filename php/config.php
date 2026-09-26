<?php
/**
 * ══════════════════════════════════════════════
 * config.php — Centralised Database Connections
 * MySQL (PDO) · MongoDB · Redis
 * ══════════════════════════════════════════════
 */

// ── CORS & JSON headers (every PHP endpoint includes this) ──
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Auth-Token');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ══════════════════════════════════════════════
// MySQL Connection (PDO — Prepared Statements)
// ══════════════════════════════════════════════
function getMySQLConnection(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $host   = 'localhost';
    $dbname = 'guvi_internship';
    $user   = 'root';
    $pass   = '';         // Change for production
    $port   = 3306;

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,   // real prepared statements
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
        exit;
    }

    return $pdo;
}

// ══════════════════════════════════════════════
// MongoDB Connection
// Requires: composer require mongodb/mongodb
// ══════════════════════════════════════════════
function getMongoCollection(string $collectionName): MongoDB\Collection {
    static $db = null;
    if ($db === null) {
        require_once __DIR__ . '/../vendor/autoload.php';   // Composer autoloader

        $uri    = 'mongodb://localhost:27017';
        $dbName = 'guvi_internship';

        try {
            $client = new MongoDB\Client($uri);
            $db     = $client->selectDatabase($dbName);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'MongoDB connection failed.']);
            exit;
        }
    }

    return $db->selectCollection($collectionName);
}

// ══════════════════════════════════════════════
// Redis Connection
// Requires: PHP Redis extension (phpredis)
// ══════════════════════════════════════════════
function getRedisClient(): Redis {
    static $redis = null;
    if ($redis !== null) return $redis;

    try {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        // $redis->auth('password');   // Uncomment if Redis requires auth
    } catch (RedisException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Redis connection failed.']);
        exit;
    }

    return $redis;
}

// ══════════════════════════════════════════════
// Session Token Helpers (Redis-based)
// ══════════════════════════════════════════════
define('SESSION_TTL', 86400);   // 24 hours in seconds

/**
 * Create a new session in Redis and return the token.
 */
function createSession(array $userData): string {
    $token = bin2hex(random_bytes(32));               // 64-char hex token
    $redis = getRedisClient();
    $redis->setex("session:{$token}", SESSION_TTL, json_encode($userData));
    return $token;
}

/**
 * Validate token and return session data, or null if invalid/expired.
 */
function validateSession(string $token): ?array {
    $redis = getRedisClient();
    $data  = $redis->get("session:{$token}");
    if (!$data) return null;

    // Refresh TTL on each validated access (sliding expiration)
    $redis->expire("session:{$token}", SESSION_TTL);

    return json_decode($data, true);
}

/**
 * Destroy a session (logout).
 */
function destroySession(string $token): void {
    $redis = getRedisClient();
    $redis->del("session:{$token}");
}

/**
 * Extract token from the X-Auth-Token request header.
 */
function getTokenFromHeader(): ?string {
    $headers = getallheaders();
    // Header names are case-insensitive
    foreach ($headers as $name => $value) {
        if (strtolower($name) === 'x-auth-token') {
            return trim($value);
        }
    }
    return null;
}

/**
 * Send a JSON response and exit.
 */
function jsonResponse(bool $success, string $message, array $data = [], int $code = 200): void {
    http_response_code($code);
    $payload = ['success' => $success, 'message' => $message];
    if (!empty($data)) $payload['data'] = $data;
    echo json_encode($payload);
    exit;
}
