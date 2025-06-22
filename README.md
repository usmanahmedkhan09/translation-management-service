# Translation Management Service

A high-performance Laravel-based Translation Management Service with token-based authentication, designed for scalability and enterprise use. Built as a senior developer code test demonstrating clean architecture, SOLID principles, and comprehensive testing.

## 🚀 Features

- **Multi-language Support**: Store translations for multiple locales (en, fr, es) with extensibility
- **Tag System**: Organize translations with contextual tags (mobile, desktop, web, admin, public)
- **High Performance**: Response times < 200ms for all endpoints, < 500ms for large exports
- **Scalable**: Handles 100,000+ translation records efficiently
- **Secure Authentication**: Token-based API authentication with Laravel Sanctum
- **RESTful API**: Complete CRUD operations with advanced search and filtering
- **Export Functionality**: JSON export for frontend applications
- **Comprehensive Testing**: 52 tests with 280 assertions (95%+ coverage)
- **Caching**: Redis-compatible caching with performance optimization
- **PSR-12 Compliant**: Clean, maintainable code following industry standards

## 📋 Requirements

- PHP 8.1+
- Composer
- MySQL 8.0+
- Redis (optional, for caching)
- Git

## 🛠️ Installation & Setup

### 1. Clone the Repository
```bash
git clone <repository-url>
cd translation-management-service
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Environment Configuration
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Configure Database
Edit `.env` file with your database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=translation_management_service
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 5. Create Database
```bash
# Create the database
mysql -u root -p -e "CREATE DATABASE translation_management_service;"

# Create test database (optional)
mysql -u root -p -e "CREATE DATABASE translation_management_service_test;"
```

### 6. Run Migrations
```bash
php artisan migrate
```

### 7. Seed Database
```bash
# Seed with test users and sample data
php artisan db:seed

# Or seed with large dataset (100k+ records) for performance testing
php artisan db:seed --class=LargeDatasetSeeder
```

### 8. Configure Sanctum (Authentication)
```bash
# Publish Sanctum configuration
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

## 🏃‍♂️ Running the Application

### Start the Development Server
```bash
php artisan serve
```
The API will be available at `http://localhost:8000`

### Run Tests
```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage (requires Xdebug/PCOV)
php artisan test --coverage
```

### Performance Testing
```bash
# Run performance tests
php artisan test tests/Feature/PerformanceTest.php
```

## 🔐 Authentication

The API uses Laravel Sanctum for token-based authentication. All translation endpoints require authentication.

### Test User Accounts
- **Admin**: `admin@example.com` / `password123`
- **User**: `user@example.com` / `password123`

### Get Authentication Token
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password123"}'
```

**Response:**
```json
{
  "message": "Login successful",
  "user": {...},
  "access_token": "1|abcdefghijklmnopqrstuvwxyz",
  "token_type": "Bearer"
}
```

## 📚 API Documentation

### Authentication Endpoints
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/register` | Register new user |
| POST | `/api/auth/login` | Login and get token |
| GET | `/api/auth/profile` | Get user profile |
| PUT | `/api/auth/profile` | Update user profile |
| POST | `/api/auth/logout` | Logout (revoke current token) |
| POST | `/api/auth/logout-all` | Logout from all devices |

### Translation Endpoints
All endpoints require `Authorization: Bearer {token}` header.

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/translations` | List all translations (paginated) |
| POST | `/api/translations` | Create new translation |
| GET | `/api/translations/{id}` | Get specific translation |
| PUT | `/api/translations/{id}` | Update translation |
| DELETE | `/api/translations/{id}` | Delete translation |
| GET | `/api/translations/export` | Export translations as JSON |
| GET | `/api/translations/locales` | Get available locales |
| GET | `/api/translations/tags` | Get available tags |
| GET | `/api/search/translations` | Search translations |

### Query Parameters
- `locale`: Filter by locale (en, fr, es)
- `tags[]`: Filter by tags
- `key`: Search by translation key
- `content`: Search by translation content
- `per_page`: Items per page (default: 15, max: 100)
- `page`: Page number

## 🔧 Usage Examples

### Create a Translation
```bash
curl -X POST http://localhost:8000/api/translations \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "key": "welcome.message",
    "value": "Welcome to our application",
    "locale": "en",
    "tags": ["web", "public"]
  }'
```

### Search Translations
```bash
# Search by tags
curl "http://localhost:8000/api/translations?tags[]=mobile&tags[]=web" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Search by locale
curl "http://localhost:8000/api/translations?locale=en" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Search by content
curl "http://localhost:8000/api/translations?content=welcome" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Export Translations
```bash
# Export all translations for a locale
curl "http://localhost:8000/api/translations/export?locale=en" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Export with tags filter
curl "http://localhost:8000/api/translations/export?locale=en&tags[]=web" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 🏗️ Architecture & Design

### Database Schema
- **translations**: id, key, value, locale, created_at, updated_at
- **tags**: id, name, created_at, updated_at
- **tag_translation**: tag_id, translation_id (pivot table)
- **users**: id, name, email, password, created_at, updated_at
- **personal_access_tokens**: Sanctum tokens table

### Key Design Patterns
- **Repository Pattern**: Clean data access layer
- **Service Layer**: Business logic separation
- **Factory Pattern**: Test data generation
- **Observer Pattern**: Cache invalidation
- **Middleware Pattern**: Authentication and rate limiting

### Performance Optimizations
- Database indexing on key columns
- Eager loading to prevent N+1 queries
- Redis caching with intelligent invalidation
- Query optimization and pagination
- Chunked database operations for large datasets

## 🧪 Testing

The project includes comprehensive testing with 52 tests covering:

- **Unit Tests**: Model relationships, validation, business logic
- **Feature Tests**: API endpoints, authentication, data flow
- **Performance Tests**: Response time validation
- **Integration Tests**: End-to-end functionality

### Test Coverage
- Models: 100%
- Controllers: 95%+
- API Endpoints: 100%
- Authentication: 100%
- Performance: 100%

## 📊 Performance Benchmarks

- **Translation Listing**: < 200ms (100k+ records)
- **Search Operations**: < 200ms
- **Create/Update**: < 200ms
- **Export Operations**: < 500ms (large datasets)
- **Authentication**: < 100ms

## 🚀 Production Deployment

### Environment Variables
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_DATABASE=translation_management_service
DB_USERNAME=your-db-user
DB_PASSWORD=your-secure-password

# Cache
CACHE_DRIVER=redis
REDIS_HOST=your-redis-host

# Queue (optional)
QUEUE_CONNECTION=redis
```

### Optimization Commands
```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 Code Standards

- PSR-12 coding standards
- SOLID principles
- Comprehensive documentation
- Test-driven development
- Clean architecture patterns

## 📄 License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## 🔗 Additional Documentation

- [Authentication Guide](AUTHENTICATION.md) - Detailed authentication documentation
- [API Reference](docs/api.md) - Complete API documentation (if available)
- [Performance Guide](docs/performance.md) - Performance optimization guide (if available)

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Verify database credentials in `.env`
   - Ensure MySQL service is running
   - Check database exists

2. **Authentication Not Working**
   - Verify Sanctum is properly installed
   - Check personal_access_tokens table exists
   - Ensure token is included in Authorization header

3. **Tests Failing**
   - Run `php artisan migrate --env=testing`
   - Ensure test database exists
   - Check database credentials in phpunit.xml

4. **Performance Issues**
   - Enable Redis caching
   - Verify database indexes
   - Check query optimization

## 📞 Support

For support, please open an issue in the GitHub repository or contact the development team.

---

**Built with ❤️ using Laravel, demonstrating enterprise-grade PHP development practices.**
