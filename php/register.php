<?php
/**
 * ══════════════════════════════════════════════
 * register.php — User Registration Endpoint
 * POST: { username, email, password }
 * MySQL with PDO Prepared Statements
 * ══════════════════════════════════════════════
 */

require_once __DIR__ . '/config.php';

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed.', [], 405);
}

// ── Parse JSON body ──
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    jsonResponse(false, 'Invalid JSON payload.', [], 400);
}

$username = trim($input['username'] ?? '');
$email    = trim($input['email']    ?? '');
$password =      $input['password'] ?? '';

// ══════════════════════════════════════
// Server-side Validation
// ══════════════════════════════════════
if (strlen($username) < 3 || strlen($username) > 50) {
    jsonResponse(false, 'Username must be 3–50 characters.', [], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Invalid email address.', [], 422);
}

if (strlen($password) < 6) {
    jsonResponse(false, 'Password must be at least 6 characters.', [], 422);
}

// ══════════════════════════════════════
// Check for Duplicate Username / Email
// ══════════════════════════════════════
$pdo = getMySQLConnection();

$stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1');
$stmt->execute([':username' => $username, ':email' => $email]);

if ($stmt->fetch()) {
    jsonResponse(false, 'Username or email already exists.', [], 409);
}

// ══════════════════════════════════════
// Hash Password & Insert User
// ══════════════════════════════════════
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

$stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (:u, :e, :p)");

try {
    $stmt->execute([':u' => $username, ':e' => $email, ':p' => $hashedPassword]);
} catch (PDOException $e) {
    jsonResponse(false, 'Registration failed. Please try again.', [], 500);
}

jsonResponse(true, 'Account created successfully!', ['userId' => (int) $pdo->lastInsertId()], 201);
