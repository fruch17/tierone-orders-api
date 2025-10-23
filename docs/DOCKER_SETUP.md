# Docker Setup - TierOne Orders API

## 🐳 Overview

This project has been containerized with Docker to provide a consistent development and production environment. The setup includes Apache, PHP 8.2, MySQL 8.0, Redis, and phpMyAdmin.

## 📋 Prerequisites

- **Docker Desktop** (Windows/Mac) or **Docker Engine** (Linux)
- **Docker Compose** v2.0+
- **Git** (for cloning the repository)

## 🚀 Quick Start

### Prerequisites

1. **Clone and navigate to project:**
   ```bash
   git clone https://github.com/fruch17/tierone-orders-api.git
   cd tierone-orders-api
   ```

### Option 1: Automated Setup (Recommended)

#### Windows:
```bash
# Run the setup script
docker-setup.bat
```

#### Linux/Mac:
```bash
# Make script executable and run
chmod +x docker-setup.sh
./docker-setup.sh
```

### Option 2: Manual Setup

1. **Create environment file:**
   ```bash
   cp .env.docker .env
   ```

2. **Build and start containers:**
   ```bash
   docker-compose up -d --build
   ```

3. **Generate application key:**
   ```bash
   docker-compose exec app php artisan key:generate
   ```

4. **Run database migrations (optimized):**
   ```bash
   docker-compose exec app php artisan migrate:fresh --force
   ```

5. **Run tests:**
   ```bash
   docker-compose exec app php artisan test
   ```

## 🌐 Services

| Service | URL | Description |
|---------|-----|-------------|
| **API** | http://localhost:8000 | Main Laravel application |
| **phpMyAdmin** | http://localhost:8080 | MySQL database management |
| **MySQL** | localhost:3306 | Database server |
| **Redis** | localhost:6379 | Cache and session storage |

## 📁 Project Structure

```
tierone-orders-api/
├── Dockerfile                 # PHP 8.2 + Apache container
├── docker-compose.yml         # Multi-container orchestration
├── .dockerignore              # Docker build exclusions
├── docker-setup.sh            # Linux/Mac setup script
├── docker-setup.bat           # Windows setup script
├── docker/
│   ├── apache/
│   │   └── 000-default.conf   # Apache virtual host config
│   ├── php/
│   │   └── local.ini          # PHP configuration
│   └── mysql/
│       └── init.sql           # MySQL initialization
└── .env.docker               # Docker environment template
```

## 🔧 Configuration

### Environment Variables

The `.env.docker` file contains Docker-optimized settings:

```env
APP_NAME="TierOne Orders API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=tierone_orders
DB_USERNAME=tierone_user
DB_PASSWORD=tierone_password

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=redis
REDIS_PORT=6379
```

### Apache Configuration

The Apache virtual host is configured for Laravel:

- **Document Root**: `/var/www/html/public`
- **URL Rewriting**: Enabled for Laravel routes
- **Security Headers**: X-Content-Type-Options, X-Frame-Options, X-XSS-Protection
- **PHP Settings**: Optimized for Laravel applications

### PHP Configuration

PHP is configured with:

- **Memory Limit**: 512M
- **Upload Size**: 40M
- **Execution Time**: 300s
- **OPcache**: Enabled for performance
- **Error Reporting**: Enabled for development

## 🛠️ Common Commands

### Container Management

```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# Restart services
docker-compose restart

# View logs
docker-compose logs -f

# View logs for specific service
docker-compose logs -f app
```

### Application Commands

```bash
# Access application container
docker-compose exec app bash

# Run Artisan commands
docker-compose exec app php artisan migrate
docker-compose exec app php artisan test
docker-compose exec app php artisan queue:work

# Run Composer commands
docker-compose exec app composer install
docker-compose exec app composer update
```

### Database Commands

```bash
# Access MySQL container
docker-compose exec mysql mysql -u root -p

# Backup database
docker-compose exec mysql mysqldump -u root -p tierone_orders > backup.sql

# Restore database
docker-compose exec -T mysql mysql -u root -p tierone_orders < backup.sql
```

### Development Commands

```bash
# Clear application cache
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear

# Generate application key
docker-compose exec app php artisan key:generate

# Run tests
docker-compose exec app php artisan test

# Run tests with coverage
docker-compose exec app php artisan test --coverage
```

## 🧪 Testing

### Run All Tests
```bash
docker-compose exec app php artisan test
```

### Run Specific Test Suites
```bash
# Authentication tests
docker-compose exec app php artisan test --filter AuthTest

# Order management tests
docker-compose exec app php artisan test --filter OrderTest

# Basic API tests
docker-compose exec app php artisan test --filter BasicApiTest
```

### Test Results
- **26 tests** passing
- **190 assertions** verified
- **0 failures**
- **MySQL database** testing environment
- **Optimized migrations** for faster execution

## 🔍 Troubleshooting

### Common Issues

#### 1. Port Already in Use
```bash
# Check what's using port 8000
netstat -tulpn | grep :8000

# Stop conflicting services or change port in docker-compose.yml
```

#### 2. Permission Issues
```bash
# Fix storage permissions
docker-compose exec app chown -R www-data:www-data /var/www/html/storage
docker-compose exec app chmod -R 755 /var/www/html/storage
```

#### 3. Database Connection Issues
```bash
# Check MySQL container status
docker-compose ps mysql

# View MySQL logs
docker-compose logs mysql

# Restart MySQL
docker-compose restart mysql
```

#### 4. Container Build Issues
```bash
# Rebuild containers
docker-compose down
docker-compose up -d --build --force-recreate

# Clear Docker cache
docker system prune -a
```

### Debugging

#### View Container Logs
```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f app
docker-compose logs -f mysql
docker-compose logs -f redis
```

#### Access Container Shell
```bash
# Application container
docker-compose exec app bash

# MySQL container
docker-compose exec mysql bash

# Redis container
docker-compose exec redis sh
```

## 🚀 Production Deployment

### Production Configuration

1. **Update environment variables:**
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://yourdomain.com
   ```

2. **Use production Docker Compose:**
   ```bash
   docker-compose -f docker-compose.prod.yml up -d
   ```

3. **Enable SSL/TLS** (recommended):
   - Use reverse proxy (Nginx/Traefik)
   - Configure SSL certificates
   - Update APP_URL to HTTPS

### Performance Optimization

- **OPcache**: Enabled for PHP performance
- **Redis**: Used for caching and sessions
- **MySQL**: Optimized configuration
- **Apache**: Gzip compression enabled

## 📊 Monitoring

### Health Checks

```bash
# Check container status
docker-compose ps

# Check resource usage
docker stats

# Check disk usage
docker system df
```

### Logs

```bash
# Application logs
docker-compose exec app tail -f /var/log/apache2/error.log

# MySQL logs
docker-compose logs mysql

# Redis logs
docker-compose logs redis
```

## 🔒 Security

### Security Features

- **Security Headers**: X-Content-Type-Options, X-Frame-Options, X-XSS-Protection
- **Database Isolation**: Separate MySQL container
- **Network Isolation**: Custom Docker network
- **Environment Variables**: Sensitive data in .env files

### Best Practices

- **Regular Updates**: Keep Docker images updated
- **Backup Strategy**: Regular database backups
- **Monitoring**: Monitor container health and logs
- **Access Control**: Limit container access

## 📚 Additional Resources

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Laravel Documentation](https://laravel.com/docs)
- [Apache Documentation](https://httpd.apache.org/docs/)

---

**Last Updated:** 2025-10-23  
**Docker Version:** 20.10+  
**Compose Version:** 2.0+
