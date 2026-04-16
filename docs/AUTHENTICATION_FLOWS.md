# Authentication System Documentation

## Overview

This authentication system uses **Laravel Sanctum** for API token authentication and **Laravel Fortify** for core authentication logic, supporting both:

- **Mobile Clients (Flutter)**: Token-based authentication via Bearer tokens
- **SPA Clients (React)**: Session-based authentication via cookies

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        Laravel API Backend                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────────┐     ┌──────────────────────────────────┐   │
│  │ ClientDetector  │────▶│  Determines authentication type   │   │
│  │                 │     │  - Mobile: Bearer token present  │   │
│  │ isMobile()      │     │  - SPA: Cookie/session based     │   │
│  │ isSPA()         │     └──────────────────────────────────┘   │
│  └─────────────────┘                    │                        │
│                                         ▼                        │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │                    Fortify Service                          │  │
│  │  - Login / Logout / Register                               │  │
│  │  - Password Reset / Email Verification                     │  │
│  │  - Two-Factor Authentication                              │  │
│  └───────────────────────────────────────────────────────────┘  │
│                                         │                        │
│                                         ▼                        │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │                   Sanctum                                  │  │
│  │  - Token Management (Mobile)                              │  │
│  │  - Session/Cookie Management (SPA)                         │  │
│  └───────────────────────────────────────────────────────────┘  │
│                                         │                        │
│                                         ▼                        │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │                  Socialite Service                        │  │
│  │  - Google, GitHub, Facebook, Twitter, LinkedIn            │  │
│  └───────────────────────────────────────────────────────────┘  │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
                              │
          ┌───────────────────┴───────────────────┐
          ▼                                       ▼
┌─────────────────────┐               ┌─────────────────────┐
│   Flutter Mobile    │               │     React SPA      │
│                     │               │                     │
│ • Bearer Token      │               │ • Session Cookie    │
│ • JSON responses    │               │ • CSRF protection   │
│ • No cookies        │               │ • Auto-include      │
└─────────────────────┘               └─────────────────────┘
```

---

## Client Detection Logic

The system automatically detects the client type based on the request:

```php
// File: app/Helpers/ClientDetector.php

if ($request->bearerToken() !== null) {
    return ClientDetector::TYPE_MOBILE;  // Flutter app
}
return ClientDetector::TYPE_SPA;  // React SPA
```

### Detection Criteria

| Client Type | Detection Method | Authentication Mechanism |
|-------------|-----------------|------------------------|
| Mobile | `Authorization: Bearer <token>` header present | Sanctum Personal Access Token |
| SPA | No Bearer token, request from stateful domain | Session cookie + CSRF |

---

## Authentication Flows

### 1. Registration Flow

#### Mobile (Flutter)

```
┌──────────┐                              ┌─────────────┐
│  Flutter  │                              │  Laravel    │
│   App     │                              │   API       │
└─────┬─────┘                              └──────┬──────┘
      │                                           │
      │  POST /api/register                       │
      │  Content-Type: application/json           │
      │  Accept: application/json                 │
      │  {                                       │
      │    "name": "John Doe",                   │
      │    "email": "john@example.com",         │
      │    "password": "password123",            │
      │    "password_confirmation": "password123" │
      │  }                                       │
      │──────────────────────────────────────────▶│
      │                                           │
      │                                           │  1. Validate input
      │                                           │  2. Create user
      │                                           │  3. Create Sanctum token
      │                                           │
      │  Response (201 Created):                  │
      │  {                                       │
      │    "message": "Registration successful",  │
      │    "user": { "id": "...", "name": "..." },│
      │    "token": "1|abc123..."                │
      │  }                                       │
      │◀──────────────────────────────────────────│
      │                                           │
      ▼                                           ▼
  Store token locally                            ✓ User created
```

**Curl Example:**
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

#### SPA (React)

```
┌──────────┐                              ┌─────────────┐
│  React   │                              │  Laravel    │
│   SPA     │                              │   API       │
└─────┬─────┘                              └──────┬──────┘
      │                                           │
      │  GET /api/sanctum/csrf-cookie             │
      │  Origin: http://localhost:5173            │
      │──────────────────────────────────────────▶│
      │                                           │
      │  Sets CSRF cookie (XSRF-TOKEN)            │
      │◀──────────────────────────────────────────│
      │                                           │
      │  POST /api/register                       │
      │  Content-Type: application/json           │
      │  Accept: application/json                │
      │  Origin: http://localhost:5173           │
      │  X-CSRF-TOKEN: <token>                   │
      │  Cookie: laravel_session, XSRF-TOKEN     │
      │  {                                       │
      │    "name": "John Doe",                   │
      │    "email": "john@example.com",          │
      │    "password": "password123",            │
      │    "password_confirmation": "password123"│
      │  }                                       │
      │──────────────────────────────────────────▶│
      │                                           │
      │                                           │  1. Validate CSRF token
      │                                           │  2. Validate input
      │                                           │  3. Create user
      │                                           │  4. Create session
      │                                           │
      │  Response (201 Created):                  │
      │  Set-Cookie: laravel_session=...         │
      │  {                                       │
      │    "message": "Registration successful", │
      │    "user": { "id": "...", "name": "..." }│
      │  }                                       │
      │◀──────────────────────────────────────────│
      │                                           │
      ▼                                           ▼
  Cookies stored locally                     ✓ Session created
```

**Curl Example:**
```bash
# Step 1: Get CSRF cookie
curl -X GET http://localhost:8000/api/sanctum/csrf-cookie \
  -H "Origin: http://localhost:5173" \
  -c cookies.txt

# Step 2: Register (use cookie + CSRF token)
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Origin: http://localhost:5173" \
  -H "X-CSRF-TOKEN: <csrf-token-from-cookie>" \
  -b cookies.txt \
  -c cookies.txt \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

---

### 2. Login Flow

#### Mobile (Flutter)

```
┌──────────┐                              ┌─────────────┐
│  Flutter  │                              │  Laravel    │
│   App     │                              │   API       │
└─────┬─────┘                              └──────┬──────┘
      │                                           │
      │  POST /api/login                          │
      │  Content-Type: application/json          │
      │  Accept: application/json                 │
      │  {                                       │
      │    "email": "john@example.com",          │
      │    "password": "password123"             │
      │  }                                       │
      │──────────────────────────────────────────▶│
      │                                           │
      │                                           │  1. Validate credentials
      │                                           │  2. Check 2FA if enabled
      │                                           │  3. Create Sanctum token
      │                                           │
      │  Response (200 OK):                       │
      │  {                                       │
      │    "message": "Login successful",        │
      │    "user": { "id": "...", "name": "..." },│
      │    "token": "2|xyz789..."                │
      │  }                                       │
      │◀──────────────────────────────────────────│
      │                                           │
      ▼                                           ▼
  Store token locally                       ✓ Authenticated
```

**Curl Example:**
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

#### SPA (React)

```
┌──────────┐                              ┌─────────────┐
│  React   │                              │  Laravel    │
│   SPA     │                              │   API       │
└─────┬─────┘                              └──────┬──────┘
      │                                           │
      │  GET /api/sanctum/csrf-cookie             │
      │  Origin: http://localhost:5173            │
      │──────────────────────────────────────────▶│
      │  Sets CSRF cookie                         │
      │◀──────────────────────────────────────────│
      │                                           │
      │  POST /api/login                          │
      │  Content-Type: application/json          │
      │  Accept: application/json                │
      │  Origin: http://localhost:5173           │
      │  Cookie: laravel_session, XSRF-TOKEN    │
      │  {                                       │
      │    "email": "john@example.com",         │
      │    "password": "password123"             │
      │  }                                       │
      │──────────────────────────────────────────▶│
      │                                           │
      │                                           │  1. Validate CSRF token
      │                                           │  2. Validate credentials
      │                                           │  3. Check 2FA if enabled
      │                                           │  4. Create session
      │                                           │
      │  Response (200 OK):                       │
      │  Set-Cookie: laravel_session=...        │
      │  {                                       │
      │    "message": "Login successful",        │
      │    "user": { "id": "...", "name": "..." }│
      │  }                                       │
      │◀──────────────────────────────────────────│
      │                                           │
      ▼                                           ▼
  Cookies stored locally                     ✓ Session created
```

**Curl Example:**
```bash
curl -X GET http://localhost:8000/api/sanctum/csrf-cookie \
  -H "Origin: http://localhost:5173" \
  -c cookies.txt

curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Origin: http://localhost:5173" \
  -b cookies.txt \
  -c cookies.txt \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

---

### 3. Two-Factor Authentication Flow

When 2FA is enabled, the login flow includes an additional step:

```
┌──────────┐                              ┌─────────────┐
│  Client   │                              │  Laravel    │
│           │                              │   API       │
└─────┬─────┘                              └──────┬──────┘
      │                                           │
      │  POST /api/login                          │
      │  { "email": "...", "password": "..." }   │
      │──────────────────────────────────────────▶│
      │                                           │
      │                                           │  Credentials valid
      │                                           │  2FA required
      │                                           │
      │  Response (200 OK):                       │
      │  {                                       │
      │    "two_factor": true,                   │
      │    "message": "Two-factor authentication │
      │                 required"                │
      │  }                                       │
      │◀──────────────────────────────────────────│
      │                                           │
      │  POST /api/two-factor-challenge           │
      │  { "code": "123456" }                    │
      │──────────────────────────────────────────▶│
      │                                           │
      │                                           │  Verify TOTP code
      │                                           │  Create token/session
      │                                           │
      │  Response:                               │
      │  {                                       │
      │    "message": "Login successful",        │
      │    "user": {...},                        │
      │    "token": "3|abc..."   (mobile only)  │
      │  }                                       │
      │◀──────────────────────────────────────────│
      │                                           │
      ▼                                           ▼
```

#### Mobile 2FA

```bash
# Login triggers 2FA challenge
curl -X POST http://localhost:8000/api/login \
  -H "Accept: application/json" \
  -d '{"email":"john@example.com","password":"password123"}'
# Response: {"two_factor": true, ...}

# Submit TOTP code
curl -X POST http://localhost:8000/api/two-factor-challenge \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{"code":"123456"}'
```

#### SPA 2FA

```bash
# Login triggers 2FA challenge
curl -X POST http://localhost:8000/api/login \
  -H "Accept: application/json" \
  -H "Origin: http://localhost:5173" \
  -b cookies.txt \
  -d '{"email":"john@example.com","password":"password123"}'
# Response: {"two_factor": true, ...}

# Submit TOTP code
curl -X POST http://localhost:8000/api/two-factor-challenge \
  -H "Accept: application/json" \
  -H "Origin: http://localhost:5173" \
  -b cookies.txt \
  -c cookies.txt \
  -d '{"code":"123456"}'
```

---

### 4. Social Authentication Flow (Google Example)

#### Mobile (Flutter)

```
┌──────────┐    ┌─────────────┐    ┌─────────────┐
│  Flutter  │    │  Laravel    │    │   Google    │
│   App     │    │   API       │    │   OAuth     │
└─────┬─────┘    └──────┬──────┘    └──────┬──────┘
      │                 │                  │
      │  GET /auth/social/google/redirect  │
      │───────────────────────────────────▶│
      │                 │                  │
      │  Response:                     Redirect to Google
      │  {                         ──────────────────────▶
      │    "redirect_url": "https://         │
      │      accounts.google.com/..."       │
      │  }                          │       │
      │◀────────────────────────────│       │
      │                 │                  │
      │  Open redirect URL in WebView       │
      │───────────────────────────────────▶│
      │                 │                  │
      │                 │    User logs in  │
      │                 │    & grants perm │
      │                 │◀─────────────────
      │                 │
      │  GET /auth/social/google/callback?code=...
      │───────────────────────────────────▶│
      │                 │                  │
      │                 │  1. Exchange code │
      │                 │  2. Get user info │
      │                 │  3. Find/create   │
      │                 │     user         │
      │                 │  4. Create token  │
      │                 │                  │
      │  Response:      │                  │
      │  {               │                  │
      │    "message":    │                  │
      │      "Authentication successful",    │
      │    "user": {...},│                  │
      │    "token": "4|.."│                 │
      │  }               │                  │
      │◀────────────────────────────────────│
      │                 │                  │
      ▼                 ▼                  ▼
```

**Curl Example:**
```bash
# Get redirect URL
curl -X GET http://localhost:8000/api/auth/social/google/redirect \
  -H "Accept: application/json"

# Response:
# {"redirect_url": "https://accounts.google.com/o/oauth2/auth?..."}

# After user authorizes, callback with code
# (Browser handles this redirect automatically)
curl -X GET "http://localhost:8000/api/auth/social/google/callback?code=AUTH_CODE" \
  -H "Accept: application/json"

# Response:
# {
#   "message": "Authentication successful",
#   "user": {"id": "...", "name": "...", "email": "..."},
#   "token": "4|abc123..."
# }
```

#### SPA (React)

```
┌──────────┐    ┌─────────────┐    ┌─────────────┐
│  React   │    │  Laravel    │    │   Google    │
│   SPA     │    │   API       │    │   OAuth     │
└─────┬─────┘    └──────┬──────┘    └──────┬──────┘
      │                 │                  │
      │  GET /auth/social/google/redirect  │
      │  Origin: http://localhost:5173    │
      │───────────────────────────────────▶│
      │                 │                  │
      │  Redirect to Google              │
      │◀──────────────────────────────────│
      │                 │                  │
      │                                     │
      │─────────────────────────────────────▶
      │                                     │
      │                 │    User logs in   │
      │                 │    & grants perm  │
      │                 │◀──────────────────
      │                 │
      │  GET /auth/social/google/callback?code=...
      │  Origin: http://localhost:5173
      │───────────────────────────────────▶│
      │                 │                  │
      │                 │  1. Validate     │
      │                 │     CSRF/state   │
      │                 │  2. Get user     │
      │                 │  3. Find/create   │
      │                 │  4. Create       │
      │                 │     session      │
      │                 │                  │
      │  Set-Cookie: laravel_session=...  │
      │  Response:                       │
      │  {                               │
      │    "message": "Authentication    │
      │               successful",        │
      │    "user": {...}                 │
      │  }                               │
      │◀─────────────────────────────────│
      │                 │                  │
      ▼                 ▼                  ▼
```

**Curl Example:**
```bash
# Step 1: Redirect to Google (browser handles this)
curl -X GET http://localhost:8000/api/auth/social/google/redirect \
  -H "Origin: http://localhost:5173"
# Browser will redirect to Google

# Step 2: Google redirects back with code
# Browser automatically handles the callback
```

---

### 5. Password Reset Flow

```
┌──────────┐                              ┌─────────────┐
│  Client   │                              │  Laravel    │
│           │                              │   API       │
└─────┬─────┘                              └──────┬──────┘
      │                                           │
      │  POST /api/forgot-password                 │
      │  { "email": "john@example.com" }          │
      │──────────────────────────────────────────▶│
      │                                           │
      │                                           │  Send reset email
      │                                           │
      │  Response (200 OK):                       │
      │  { "message": "Reset link sent" }         │
      │◀──────────────────────────────────────────│
      │                                           │
      │  User clicks link in email                │
      │                                           │
      │  POST /api/reset-password                 │
      │  {                                       │
      │    "email": "john@example.com",          │
      │    "password": "newpassword123",         │
      │    "password_confirmation": "newpassword123",
      │    "token": "reset-token-from-email"     │
      │  }                                       │
      │──────────────────────────────────────────▶│
      │                                           │
      │                                           │  Reset password
      │                                           │
      │  Response (200 OK):                       │
      │  { "message": "Password reset complete" }│
      │◀──────────────────────────────────────────│
      │                                           │
      ▼                                           ▼
```

**Curl Example:**
```bash
# Request password reset
curl -X POST http://localhost:8000/api/forgot-password \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email": "john@example.com"}'

# Reset password (use token from email)
curl -X POST http://localhost:8000/api/reset-password \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "newpassword123",
    "password_confirmation": "newpassword123",
    "token": "reset-token-from-email"
  }'
```

---

### 6. Token Refresh Flow (Mobile Only)

```
┌──────────┐                              ┌─────────────┐
│  Flutter  │                              │  Laravel    │
│   App     │                              │   API       │
└─────┬─────┘                              └──────┬──────┘
      │                                           │
      │  POST /api/auth/refresh-token             │
      │  Authorization: Bearer <current_token>     │
      │──────────────────────────────────────────▶│
      │                                           │
      │                                           │  1. Validate token
      │                                           │  2. Revoke old token
      │                                           │  3. Create new token
      │                                           │
      │  Response:                               │
      │  {                                       │
      │    "message": "Token refreshed",         │
      │    "token": "5|newtoken..."             │
      │  }                                       │
      │◀──────────────────────────────────────────│
      │                                           │
      ▼                                           ▼
  Update stored token                    ✓ Token rotated
```

**Curl Example:**
```bash
curl -X POST http://localhost:8000/api/auth/refresh-token \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <current_token>"
```

---

### 7. Logout Flow

#### Mobile (Flutter)

```
┌──────────┐                              ┌─────────────┐
│  Flutter  │                              │  Laravel    │
│   App     │                              │   API       │
└─────┬─────┘                              └──────┬──────┘
      │                                           │
      │  POST /api/logout                         │
      │  Authorization: Bearer <token>            │
      │──────────────────────────────────────────▶│
      │                                           │
      │                                           │  Revoke token
      │                                           │
      │  Response:                               │
      │  { "message": "Logged out successfully" } │
      │◀──────────────────────────────────────────│
      │                                           │
      ▼                                           ▼
  Clear stored token                      ✓ Token revoked
```

#### SPA (React)

```
┌──────────┐                              ┌─────────────┐
│  React   │                              │  Laravel    │
│   SPA     │                              │   API       │
└─────┬─────┘                              └──────┬──────┘
      │                                           │
      │  POST /api/logout                         │
      │  Cookie: laravel_session=...              │
      │──────────────────────────────────────────▶│
      │                                           │
      │                                           │  1. Invalidate session
      │                                           │  2. Regenerate CSRF token
      │                                           │
      │  Response:                               │
      │  Set-Cookie: laravel_session=deleted    │
      │  { "message": "Logged out successfully" }│
      │◀──────────────────────────────────────────│
      │                                           │
      ▼                                           ▼
  Clear cookies                       ✓ Session destroyed
```

---

## Making Authenticated Requests

### Mobile (Flutter)

Include the token in the `Authorization` header:

```bash
# Get user profile
curl -X GET http://localhost:8000/api/user \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <token>"

# Update profile
curl -X PUT http://localhost:8000/api/user/profile-information \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"name": "John Updated"}'

# Delete account / logout
curl -X POST http://localhost:8000/api/logout \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <token>"
```

### SPA (React)

Include cookies automatically with every request:

```bash
# Get user profile (cookies sent automatically)
curl -X GET http://localhost:8000/api/user \
  -H "Accept: application/json" \
  -H "Origin: http://localhost:5173" \
  -b cookies.txt

# Update profile
curl -X PUT http://localhost:8000/api/user/profile-information \
  -H "Accept: application/json" \
  -H "Origin: http://localhost:5173" \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -c cookies.txt \
  -d '{"name": "John Updated"}'
```

---

## API Response Formats

### Success Responses

**Registration/Login (Mobile):**
```json
{
  "message": "Registration successful",
  "user": {
    "id": "uuid-here",
    "name": "John Doe",
    "email": "john@example.com"
  },
  "token": "1|abc123def456..."
}
```

**Registration/Login (SPA):**
```json
{
  "message": "Registration successful",
  "user": {
    "id": "uuid-here",
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```

**Get User:**
```json
{
  "user": {
    "id": "uuid-here",
    "name": "John Doe",
    "email": "john@example.com",
    "email_verified_at": "2024-01-15T10:30:00.000000Z",
    "two_factor_confirmed_at": null
  }
}
```

### Error Responses

**Validation Error:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

**Authentication Failed:**
```json
{
  "message": "Invalid credentials."
}
```

**Two-Factor Required:**
```json
{
  "two_factor": true
}
```

**Unauthenticated:**
```json
{
  "message": "Unauthenticated."
}
```

---

## Security Features

### 1. CSRF Protection (SPA Only)

- CSRF tokens are automatically handled for SPA requests from stateful domains
- The `/api/sanctum/csrf-cookie` endpoint sets the `XSRF-TOKEN` cookie
- All stateful POST/PUT/DELETE requests must include `X-CSRF-TOKEN` header

### 2. Rate Limiting

- Login attempts are limited to 5 per minute per email/IP combination
- Two-factor challenge attempts are limited to 5 per minute

### 3. Token Security

- Sanctum tokens are hashed (SHA-256) before storage
- Tokens can be revoked individually or all at once
- Mobile apps should securely store tokens

### 4. Session Security (SPA)

- Sessions use secure, HTTP-only cookies
- `SameSite=None` allows cross-origin requests from SPA
- Sessions are invalidated on logout

### 5. Password Security

- Passwords are hashed using bcrypt
- Password confirmation required for sensitive operations

---

## Environment Configuration

### Required Environment Variables

```env
# Application
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:5173

# CORS & Stateful Domains (for SPA)
CORS_ALLOWED_ORIGINS=http://localhost:5173
SANCTUM_STATEFUL_DOMAINS=localhost:5173

# Session (for SPA)
SESSION_DRIVER=cookie
SESSION_SAME_SITE=none
SESSION_SECURE_COOKIE=true

# Google OAuth (example)
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8000/api/auth/social/google/callback

# GitHub OAuth (example)
GITHUB_CLIENT_ID=your-github-client-id
GITHUB_CLIENT_SECRET=your-github-client-secret
GITHUB_REDIRECT_URI=http://localhost:8000/api/auth/social/github/callback
```

### CORS Configuration

The application uses Laravel's built-in CORS handling configured in `bootstrap/app.php`:

```php
$middleware->api(prepend: [
    \Illuminate\Http\Middleware\HandleCors::class,
]);
```

Allowed origins should be configured in `SANCTUM_STATEFUL_DOMAINS` and `CORS_ALLOWED_ORIGINS`.

---

## Database Schema

### Users Table

The `users` table includes standard fields plus social login columns:

| Column | Type | Description |
|--------|------|-------------|
| id | UUID | Primary key |
| name | string | User's name |
| email | string | Unique email |
| email_verified_at | timestamp | Email verification date |
| password | string | Hashed password |
| remember_token | string | Remember me token |
| gender | string | Optional gender |
| phone_number | string | Optional phone |
| google_id | string | Google OAuth ID |
| github_id | string | GitHub OAuth ID |
| facebook_id | string | Facebook OAuth ID |
| twitter_id | string | Twitter OAuth ID |
| linkedin_id | string | LinkedIn OAuth ID |
| two_factor_secret | text | 2FA secret (encrypted) |
| two_factor_recovery_codes | text | 2FA recovery codes (encrypted) |
| two_factor_confirmed_at | timestamp | 2FA confirmation time |

### Personal Access Tokens Table

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| tokenable_type | string | Model type (User) |
| tokenable_id | uuid | User ID |
| name | string | Token name |
| token | string | Hashed token |
| abilities | text | Token abilities (JSON) |
| last_used_at | timestamp | Last usage |
| expires_at | timestamp | Expiration time |
| created_at | timestamp | Creation time |
| updated_at | timestamp | Last update |

---

## Troubleshooting

### Common Issues

#### 1. CORS Errors

**Symptom:** `Access-Control-Allow-Origin` missing errors

**Solution:**
- Ensure `SANCTUM_STATEFUL_DOMAINS` includes your frontend domain
- Ensure `CORS_ALLOWED_ORIGINS` includes your frontend domain
- For development, use `http://localhost:5173` (Vite default)

#### 2. CSRF Token Mismatch

**Symptom:** `CSRF token mismatch` error

**Solution:**
- Call `/api/sanctum/csrf-cookie` before making POST requests
- Include `X-CSRF-TOKEN` header with value from `XSRF-TOKEN` cookie
- Ensure cookies are being sent with requests

#### 3. Token Not Accepted

**Symptom:** `Unauthenticated` error even with token

**Solution:**
- Verify token format: `1|abc123...` (prefix + token)
- Ensure `Authorization: Bearer <token>` header format
- Check token hasn't been revoked

#### 4. Social Login Not Working

**Symptom:** OAuth callback fails or returns error

**Solution:**
- Verify OAuth credentials in `.env`
- Ensure redirect URI in OAuth provider matches `config/services.php`
- Check Google/GitHub/etc. console for error details

---

## Testing the Authentication System

### Using Postman or curl

#### Mobile Testing

```bash
# 1. Register
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"name":"Test User","email":"test@example.com","password":"password123","password_confirmation":"password123"}'

# 2. Use returned token
TOKEN="1|abc..."
curl -X GET http://localhost:8000/api/user \
  -H "Authorization: Bearer $TOKEN"

# 3. Logout
curl -X POST http://localhost:8000/api/logout \
  -H "Authorization: Bearer $TOKEN"
```

#### SPA Testing

```bash
# 1. Get CSRF cookie
curl -X GET http://localhost:8000/api/sanctum/csrf-cookie \
  -H "Origin: http://localhost:5173" \
  -c cookies.txt

# 2. Register (with CSRF)
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Origin: http://localhost:5173" \
  -H "X-CSRF-TOKEN: <from-XSRF-TOKEN-cookie>" \
  -b cookies.txt \
  -c cookies.txt \
  -d '{"name":"Test User","email":"test@example.com","password":"password123","password_confirmation":"password123"}'

# 3. Get user (cookies sent automatically)
curl -X GET http://localhost:8000/api/user \
  -H "Accept: application/json" \
  -H "Origin: http://localhost:5173" \
  -b cookies.txt
```
