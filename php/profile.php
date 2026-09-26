<?php
/**
 * ══════════════════════════════════════════════
 * profile.php — Protected Profile Endpoint
 * GET:  Fetch profile (MongoDB) — requires X-Auth-Token
 * POST: Upsert profile (MongoDB) — requires X-Auth-Token
 * Token validated against Redis on every request
 * ══════════════════════════════════════════════
 */

require_once __DIR__ . '/config.php';

// ══════════════════════════════════════
// Authenticate via Redis Token
// ══════════════════════════════════════
// Read token from X-Auth-Token header (Apache/Nginx via getallheaders or $_SERVER)
$token = null;
if (isset($_SERVER['HTTP_X_AUTH_TOKEN'])) {
    $token = trim($_SERVER['HTTP_X_AUTH_TOKEN']);
}
if (!$token) {
    $token = getTokenFromHeader();   // fallback for other server configs
}

if (!$token) {
    jsonResponse(false, 'Authentication required. Please log in.', [], 401);
}

$session = validateSession($token);

if (!$session) {
    jsonResponse(false, 'Session expired or invalid. Please log in again.', [], 401);
}

$userId   = (int) $session['userId'];
$username = $session['username'];
$email    = $session['email'];

// ══════════════════════════════════════
// GET — Fetch Profile from MongoDB
// ══════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $collection = getMongoCollection('profiles');
    $profileDoc = $collection->findOne(['user_id' => $userId]);

    $profile = null;
    if ($profileDoc) {
        $profile = [
            'age'     => $profileDoc['age']     ?? '',
            'dob'     => $profileDoc['dob']     ?? '',
            'contact' => $profileDoc['contact'] ?? '',
            'address' => $profileDoc['address'] ?? '',
            'bio'     => $profileDoc['bio']     ?? '',
        ];
    }

    jsonResponse(true, 'Profile loaded.', [
        'user'    => [
            'id'       => $userId,
            'username' => $username,
            'email'    => $email,
        ],
        'profile' => $profile,
    ]);
}

// ══════════════════════════════════════
// POST — Upsert Profile in MongoDB
// ══════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        jsonResponse(false, 'Invalid JSON payload.', [], 400);
    }

    // ── Server-side validation ──
    $age = isset($data['age']) ? (int) $data['age'] : null;
    $dob = trim($data['dob'] ?? '');
    $contact = trim($data['contact'] ?? '');

    if ($age !== null && ($age < 1 || $age > 150)) {
        jsonResponse(false, 'Age must be between 1 and 150.', [], 422);
    }

    if ($dob && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
        jsonResponse(false, 'Date of birth must be in YYYY-MM-DD format.', [], 422);
    }

    if ($contact && !preg_match('/^[\d+\-\s()]{7,15}$/', $contact)) {
        jsonResponse(false, 'Invalid contact number format.', [], 422);
    }

    // ── Upsert into MongoDB (exact GUVI spec pattern) ──
    $collection = getMongoCollection('profiles');

    try {
        $collection->updateOne(
            ['user_id' => (int) $userId],
            ['$set' => [
                'user_id'    => (int) $userId,
                'age'        => $age,
                'dob'        => $dob,
                'contact'    => $contact,
                'address'    => trim($data['address'] ?? ''),
                'bio'        => trim($data['bio'] ?? ''),
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ]],
            ['upsert' => true]
        );
    } catch (Exception $e) {
        jsonResponse(false, 'Failed to save profile. Please try again.', [], 500);
    }

    jsonResponse(true, 'Profile updated successfully.');
}

// ── Unsupported methods ──
jsonResponse(false, 'Method not allowed.', [], 405);
