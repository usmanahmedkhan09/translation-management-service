# Authentication Guide

This Translation Management Service uses Laravel Sanctum for token-based API authentication.

## Authentication Endpoints

### Register a New User
```bash
POST /api/auth/register

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response:**
```json
{
  "message": "User registered successfully",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "created_at": "2025-06-22T09:00:00.000000Z",
    "updated_at": "2025-06-22T09:00:00.000000Z"
  },
  "access_token": "1|abcdefghijklmnopqrstuvwxyz",
  "token_type": "Bearer"
}
```

### Login
```bash
POST /api/auth/login

{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "created_at": "2025-06-22T09:00:00.000000Z",
    "updated_at": "2025-06-22T09:00:00.000000Z"
  },
  "access_token": "2|abcdefghijklmnopqrstuvwxyz",
  "token_type": "Bearer"
}
```

### Get User Profile
```bash
GET /api/auth/profile
Authorization: Bearer {token}
```

### Update Profile
```bash
PUT /api/auth/profile
Authorization: Bearer {token}

{
  "name": "John Smith",
  "email": "johnsmith@example.com"
}
```

### Logout (Current Device)
```bash
POST /api/auth/logout
Authorization: Bearer {token}
```

### Logout (All Devices)
```bash
POST /api/auth/logout-all
Authorization: Bearer {token}
```

## Using Authentication with Translation Endpoints

All translation endpoints now require authentication. Include the Bearer token in the Authorization header:

```bash
# Get all translations
GET /api/translations
Authorization: Bearer {your_token}

# Create a translation
POST /api/translations
Authorization: Bearer {your_token}
Content-Type: application/json

{
  "key": "welcome.message",
  "value": "Welcome to our application",
  "locale": "en",
  "tags": ["web", "public"]
}

# Export translations
GET /api/translations/export?locale=en
Authorization: Bearer {your_token}
```

## Example Usage

### 1. Register or Login to get a token
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password123"}'
```

### 2. Use the token for API requests
```bash
curl -X GET http://localhost:8000/api/translations \
  -H "Authorization: Bearer 1|abcdefghijklmnopqrstuvwxyz"
```

## Test Users

The system comes with pre-seeded test users:

- **Admin User**
  - Email: `admin@example.com`
  - Password: `password123`

- **Regular User**
  - Email: `user@example.com`
  - Password: `password123`

## Security Features

- ✅ Password hashing with bcrypt
- ✅ Token-based authentication using Laravel Sanctum
- ✅ Token revocation on logout
- ✅ Multi-device token management
- ✅ Protected API endpoints
- ✅ Input validation and sanitization
- ✅ Rate limiting (Laravel default)

## Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 422 Validation Error
```json
{
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field is required."]
  }
}
```

### 403 Forbidden
```json
{
  "message": "This action is unauthorized."
}
``` 