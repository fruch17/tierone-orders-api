# TierOne Orders API - Technical Challenge

[![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-orange.svg)](https://mysql.com)
[![Tests](https://img.shields.io/badge/Tests-25%20passed-green.svg)](https://github.com/fruch17/tierone-orders-api)

A **multi-tenant Order Management API** built with Laravel 11 for the TierOne Engineering technical challenge. Features role-based authentication, order management, asynchronous invoice generation, and comprehensive testing following SOLID principles.

## 🚀 Quick Start

### Prerequisites

- **PHP 8.2+** with extensions: BCMath, Ctype, cURL, DOM, Fileinfo, JSON, Mbstring, OpenSSL, PCRE, PDO, Tokenizer, XML
- **MySQL 8.0+** or **MariaDB 10.3+**
- **Composer 2.0+**

> **Note**: This is a pure API project. Frontend assets (Node.js, Vite, Tailwind) are included by default but not required for API functionality.

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/fruch17/tierone-orders-api.git
   cd tierone-orders-api
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database configuration**
   ```bash
   # Update .env with your database credentials
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database_name
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. **Create database**
   ```bash
   mysql -u your_username -p -e "CREATE DATABASE your_database_name;"
   ```

6. **Run migrations**
   ```bash
   php artisan migrate
   ```

7. **Start the server**
   ```bash
   # Option 1: Laravel development server
   php artisan serve
   
   # Option 2: Use your preferred web server (Apache, Nginx, etc.)
   # Configure your web server to point to the 'public' directory
   # The API will be available at your configured domain/port
   ```

   The API will be available at:
   - Laravel server: `http://localhost:8000`
   - Custom server: `http://your-domain.com` (or your configured URL)

## 🌐 Web Server Configuration

### Apache Configuration
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/tierone-orders-api/public
    
    <Directory /path/to/tierone-orders-api/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Nginx Configuration
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/tierone-orders-api/public;
    
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### WAMP/XAMPP Setup
1. Copy project to `htdocs` or `www` directory
2. Access via `http://localhost/tierone-orders-api/public`
3. Update `.env` with correct `APP_URL`

## 🧪 Testing

### Run Tests
```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --filter AuthTest
php artisan test --filter OrderTest
php artisan test --filter BasicApiTest

# Run with debug information
php artisan test --debug
```

### Test Results
- **25 tests** passing
- **75 assertions** verified
- **0 failures**
- **MySQL database** testing environment

## 📚 API Documentation

### Authentication Endpoints
- `POST /api/auth/register` - Register new admin user
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout
- `GET /api/auth/me` - Get current user
- `POST /api/auth/register-staff` - Register staff (admin only)

### Order Management Endpoints
- `POST /api/orders` - Create new order
- `GET /api/orders/{id}` - Get order by ID
- `GET /api/orders` - List user's orders
- `GET /api/clients/{id}/orders` - List client orders

### Quick API Test
```bash
# Register a new user (replace localhost:8000 with your server URL)
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "John Doe",
    "company_name": "ACME Corp",
    "company_email": "contact@acme.com",
    "email": "john@acme.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

## 📖 Detailed Documentation

### Core Features
- [**API Authentication**](docs/API_AUTH.md) - Complete authentication flow
- [**Order Management**](docs/API_ORDERS.md) - Order CRUD operations
- [**API Status Codes**](docs/API_STATUS_CODES.md) - HTTP status codes and responses
- [**Multi-tenancy**](docs/MULTITENANCY_IMPLEMENTATION.md) - Client isolation

### Architecture & Implementation
- [**Role-Based Multi-tenancy**](docs/ROLE_BASED_MULTITENANCY.md) - Admin/Staff roles
- [**Order Audit Trail**](docs/ORDER_AUDIT_TRAIL.md) - User tracking
- [**AuthService Refactoring**](docs/AUTHSERVICE_REFACTORING.md) - Service layer
- [**Optimized Migrations**](docs/MIGRACIONES_OPTIMIZADAS.md) - Database schema

### Asynchronous Processing
- [**Order to Job Process**](docs/ORDER_TO_JOB_PROCESS_FLOW.md) - Invoice generation

### Testing Documentation
- [**Testing Guide**](docs/TESTING_GUIDE.md) - Step-by-step testing
- [**Testing Documentation**](docs/TESTING_DOCUMENTATION.md) - Comprehensive testing
- [**Testing Summary**](docs/TESTING_SUMMARY.md) - Test results analysis

### Development Approach
- [**Development Approach (English)**](docs/MY_DEVELOPMENT_APPROACH.md) - Technical methodology
- [**Enfoque de Desarrollo (Español)**](docs/MI_ENFOQUE_DESARROLLO.md) - Metodología técnica

## 🛠️ Postman Collection

Import the Postman collection for easy API testing:

1. **Collection**: [docs/postman/TierOne Orders API - Challenge.postman_collection.json](docs/postman/TierOne%20Orders%20API%20-%20Challenge.postman_collection.json)
2. **Import** into Postman
3. **Update** environment variables:
   - `base_url`: `http://localhost:8000` (or your server URL)
   - `token`: (auto-set after login)
   - `user_id`: (auto-set after login)

## 🏗️ Architecture

### Technology Stack
- **Framework**: Laravel 11
- **Database**: MySQL 8.0+
- **Authentication**: Laravel Sanctum
- **Queue**: Database driver
- **Testing**: PHPUnit with MySQL

### Key Features
- ✅ **Multi-tenant Architecture** - Client data isolation
- ✅ **Role-Based Access Control** - Admin/Staff roles
- ✅ **RESTful API** - JSON responses with proper status codes
- ✅ **Service Layer Pattern** - Business logic separation
- ✅ **Comprehensive Testing** - Feature and unit tests
- ✅ **Asynchronous Processing** - Queue jobs for invoice generation
- ✅ **Audit Trail** - User tracking for orders
- ✅ **SOLID Principles** - Clean, maintainable code

### Database Schema
- **clients** - Client companies/organizations
- **users** - Authentication and multi-tenancy
- **orders** - Order management with client isolation
- **order_items** - Order line items
- **jobs** - Queue job processing
- **personal_access_tokens** - API authentication

## 🔧 Configuration

### Environment Variables
```env
APP_NAME="TierOne Orders API"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password

QUEUE_CONNECTION=database
```

### Queue Configuration
```bash
# Process queue jobs
php artisan queue:work

# Monitor queue status
php artisan queue:monitor
```

## 🎯 Challenge Requirements

### ✅ Completed Features
- **Client Management** - Multi-tenant user system
- **Order Creation** - POST /api/orders with validation
- **Order Retrieval** - GET /api/orders/{id}
- **Client Order Listing** - GET /api/clients/{id}/orders
- **Asynchronous Invoice Generation** - Queue job processing
- **Authentication** - Laravel Sanctum token-based auth
- **Testing** - Comprehensive test suite

### 🏆 Additional Implementations
- **Role-Based Access Control** - Admin/Staff roles
- **Audit Trail** - User tracking for orders
- **Service Layer Architecture** - SOLID principles
- **Comprehensive Documentation** - Technical guides
- **Postman Collection** - API testing
- **Error Handling** - JSON error responses
- **Optimized Migrations** - Clean database schema

## 📊 Project Statistics

- **25 Tests** - All passing
- **75 Assertions** - Verified
- **10 Test Files** - Feature and unit tests
- **3 Factory Files** - Test data generation
- **15+ Documentation Files** - Comprehensive guides
- **1 Postman Collection** - API testing

## 🤝 Contributing

This project was developed as part of the TierOne Engineering technical challenge. The implementation demonstrates:

- **Laravel Expertise** - Modern Laravel 11 features
- **SOLID Principles** - Clean architecture
- **TDD Approach** - Test-driven development
- **Professional Standards** - Production-ready code
- **Comprehensive Documentation** - Technical guides

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 Developer

**Freddy Urbina**  
Email: fruch17@gmail.com  
GitHub: [@fruch17](https://github.com/fruch17)

---

**Repository**: [https://github.com/fruch17/tierone-orders-api](https://github.com/fruch17/tierone-orders-api)

*Built with ❤️ for the TierOne Engineering technical challenge*