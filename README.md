# UserHub — Full Stack User Management System

> GUVI Internship Assignment — Register → Login → Profile  
> MySQL · MongoDB · Redis · PHP · jQuery AJAX · Bootstrap 5

---

## 📁 Folder Structure

```
klaritilms/
├── assets/                  # Static assets (images, icons — placeholder)
├── css/
│   └── style.css            # Global stylesheet
├── js/
│   ├── register.js          # Registration form handler
│   ├── login.js             # Login form handler
│   └── profile.js           # Profile dashboard handler
├── php/
│   ├── config.php           # DB connections (MySQL, MongoDB, Redis)
│   ├── register.php         # Registration API endpoint
│   ├── login.php            # Login/Logout API endpoint
│   └── profile.php          # Profile CRUD API endpoint
├── index.html               # Landing page
├── register.html            # Registration page
├── login.html               # Login page
├── profile.html             # Protected profile dashboard
├── setup.sql                # MySQL schema setup
├── composer.json            # PHP dependencies
└── README.md                # This file
```

---

## 🛠️ Prerequisites

| Service | Version | Purpose |
|---------|---------|---------|
| **PHP** | ≥ 7.4 | Backend scripts |
| **MySQL** | ≥ 5.7 | User authentication data |
| **MongoDB** | ≥ 4.4 | Extended profile storage |
| **Redis** | ≥ 6.0 | Session token management |
| **Composer** | ≥ 2.0 | PHP dependency management |

### Required PHP Extensions
- `pdo_mysql` — MySQL PDO driver
- `redis` — phpredis extension ([pecl install redis](https://pecl.php.net/package/redis))
- `mongodb` — MongoDB driver ([pecl install mongodb](https://pecl.php.net/package/mongodb))

---

## ⚡ Setup Instructions

### 1. Clone / Place Files
Ensure all files are placed in your web server's document root (e.g., `htdocs/klaritilms/` for XAMPP).

### 2. MySQL Database
```bash
mysql -u root -p < setup.sql
```
This creates the `guvi_internship` database and `users` table.

### 3. MongoDB
Ensure MongoDB is running on `localhost:27017`. The `guvi_internship` database and `profiles` collection are created automatically on first write.

### 4. Redis
Ensure Redis is running on `127.0.0.1:6379`.
```bash
redis-server
```

### 5. PHP Dependencies (MongoDB Library)
```bash
cd /path/to/klaritilms
composer install
```
This installs `mongodb/mongodb` from the `composer.json`.

### 6. Configuration
Edit `php/config.php` if your database credentials differ from defaults:
- **MySQL**: host, dbname, user, pass (defaults: `localhost`, `guvi_internship`, `root`, empty)
- **MongoDB**: URI (default: `mongodb://localhost:27017`)
- **Redis**: host, port (default: `127.0.0.1:6379`)

### 7. Start the Server
```bash
# Using PHP built-in server (development)
php -S localhost:8000

# Or use XAMPP/WAMP/MAMP and access via:
# http://localhost/klaritilms/
```

---

## 🔐 Architecture

### Authentication Flow
```
Register → MySQL (bcrypt hash) → Redirect to Login
Login    → MySQL verify → Redis token (bin2hex(random_bytes(32))) → localStorage
Profile  → X-Auth-Token header → Redis validate → MongoDB CRUD
Logout   → Redis DEL session:{token} → Clear localStorage
```

### Key Design Decisions

| Requirement | Implementation |
|---|---|
| No HTML form submissions | All interactions via `$.ajax()` with JSON payloads |
| No cookies / PHP sessions | Redis token stored in `localStorage`, sent via `X-Auth-Token` header |
| Prepared statements only | All MySQL queries use PDO prepared statements with named parameters |
| Password security | `password_hash()` with `PASSWORD_BCRYPT` cost 12 + `password_verify()` |
| Session expiration | Redis `SETEX` with 86400s TTL, sliding expiry on each validated access |
| Separation of concerns | HTML, CSS, JS, PHP in strictly separate files |

---

## 📡 API Endpoints

### `POST /php/register.php`
```json
// Request
{ "username": "john", "email": "john@example.com", "password": "secret123" }

// Response (201)
{ "success": true, "message": "Account created successfully!", "data": { "userId": 1 } }
```

### `POST /php/login.php`
```json
// Request
{ "identifier": "john@example.com", "password": "secret123" }

// Response (200)
{
  "success": true,
  "message": "Login successful.",
  "data": {
    "token": "a1b2c3...64-char-hex",
    "user": { "userId": 1, "username": "john", "email": "john@example.com" }
  }
}
```

### `POST /php/login.php` (Logout)
```json
// Request (+ X-Auth-Token header)
{ "action": "logout" }

// Response (200)
{ "success": true, "message": "Logged out successfully." }
```

### `GET /php/profile.php`
```
Headers: X-Auth-Token: <token>

// Response (200)
{
  "success": true,
  "message": "Profile loaded.",
  "data": {
    "user": { "id": 1, "username": "john", "email": "john@example.com" },
    "profile": { "age": 25, "dob": "1999-06-15", "contact": "9876543210", "address": "...", "bio": "..." }
  }
}
```

### `POST /php/profile.php`
```json
// Request (+ X-Auth-Token header)
{ "age": 25, "dob": "1999-06-15", "contact": "9876543210", "address": "Chennai", "bio": "Developer" }

// Response (200)
{ "success": true, "message": "Profile updated successfully." }
```

---

## 📝 License
Academic project — GUVI Internship Assignment.
