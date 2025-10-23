@echo off
REM TierOne Orders API - Docker Setup Script for Windows
REM This script sets up the Docker environment for the TierOne Orders API

echo 🐳 Setting up TierOne Orders API with Docker...

REM Check if Docker is installed
docker --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Docker is not installed. Please install Docker Desktop first.
    pause
    exit /b 1
)

REM Check if Docker Compose is installed
docker-compose --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Docker Compose is not installed. Please install Docker Compose first.
    pause
    exit /b 1
)

REM Create .env file from .env.docker if it doesn't exist
if not exist .env (
    echo 📝 Creating .env file...
    copy .env.docker .env
    echo ✅ .env file created
) else (
    echo ✅ .env file already exists
)

REM Build and start containers
echo 🏗️ Building Docker containers...
docker-compose up -d --build

REM Wait for MySQL to be ready
echo ⏳ Waiting for MySQL to be ready...
timeout /t 30 /nobreak >nul

REM Generate APP_KEY
echo 🔑 Generating APP_KEY...
docker-compose exec app php artisan key:generate

REM Run migrations
echo 📊 Running database migrations...
docker-compose exec app php artisan migrate --force

REM Run tests
echo 🧪 Running tests...
docker-compose exec app php artisan test

echo.
echo 🎉 Setup complete!
echo.
echo 📋 Services available:
echo   🌐 API: http://localhost:8000
echo   🗄️  phpMyAdmin: http://localhost:8080
echo   📊 MySQL: localhost:3306
echo   🔴 Redis: localhost:6379
echo.
echo 📚 Useful commands:
echo   docker-compose up -d          # Start services
echo   docker-compose down           # Stop services
echo   docker-compose logs -f        # View logs
echo   docker-compose exec app bash  # Access container
echo.
pause
