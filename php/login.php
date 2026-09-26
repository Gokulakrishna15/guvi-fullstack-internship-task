<?php
/**
 * ══════════════════════════════════════════════
 * login.php — Login & Logout Endpoint
 * POST login:  { identifier, password }
 * POST logout: { action: "logout" }  + X-Auth-Token header
 * Redis session token management (no session_start)
 * ══════════════════════════════════════════════
 */

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed.', [], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    jsonResponse(false, 'Invalid JSON payload.', [], 400);
}

// ══════════════════════════════════════
// Handle LOGOUT
// ══════════════════════════════════════
if (isset($input['action']) && $input['action'] === 'logout') {
    $token = getTokenFromHeader();
    if ($token) {
        destroySession($token);
    }
    echo json_encode(['status' => 'success', 'message' => 'Logged out successfully.']);
    exit;
}

// ══════════════════════════════════════
// Handle LOGIN
// ══════════════════════════════════════
$identifier = trim($input['identifier'] ?? '');
$password   =      $input['password']   ?? '';

if (!$identifier || !$password) {
    echo json_encode(['status' => 'error', 'message' => 'Email/Username and password are required.']);
    http_response_code(422);
    exit;
}

$pdo = getMySQLConnection();

// ── Look up user by email OR username (prepared statement) ──
$stmt = $pdo->prepare(
    'SELECT id, username, email, password
     FROM users
     WHERE email = :id OR username = :id
     LIMIT 1'
);
$stmt->execute([':id' => $identifier]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid credentials.']);
    exit;
}

// ── Verify password ──
if (!password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid credentials.']);
    exit;
}

// ══════════════════════════════════════
// Create Redis Session Token
// ══════════════════════════════════════
$sessionData = [
    'userId'   => (int) $user['id'],
    'username' => $user['username'],
    'email'    => $user['email'],
];

$token = createSession($sessionData);

// ── Return flat response: { status, token, user } ──
echo json_encode([
    'status' => 'success',
    'token'  => $token,
    'user'   => $sessionData,
]);
exit;
