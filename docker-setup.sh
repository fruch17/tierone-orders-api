#!/bin/bash

# TierOne Orders API - Docker Setup Script
# This script sets up the Docker environment for the TierOne Orders API

echo "🐳 Setting up TierOne Orders API with Docker..."

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

# Create .env file from .env.docker if it doesn't exist
if [ ! -f .env ]; then
    echo "📝 Creating .env file..."
    cp .env.docker .env
    echo "✅ .env file created"
else
    echo "✅ .env file already exists"
fi

# Generate APP_KEY if not set
if ! grep -q "APP_KEY=base64:" .env; then
    echo "🔑 Generating APP_KEY..."
    docker-compose exec app php artisan key:generate
    echo "✅ APP_KEY generated"
else
    echo "✅ APP_KEY already exists"
fi

# Build and start containers
echo "🏗️ Building Docker containers..."
docker-compose up -d --build

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL to be ready..."
sleep 30

# Run migrations
echo "📊 Running database migrations..."
docker-compose exec app php artisan migrate --force

# Run tests
echo "🧪 Running tests..."
docker-compose exec app php artisan test

echo ""
echo "🎉 Setup complete!"
echo ""
echo "📋 Services available:"
echo "  🌐 API: http://localhost:8000"
echo "  🗄️  phpMyAdmin: http://localhost:8080"
echo "  📊 MySQL: localhost:3306"
echo "  🔴 Redis: localhost:6379"
echo ""
echo "📚 Useful commands:"
echo "  docker-compose up -d          # Start services"
echo "  docker-compose down           # Stop services"
echo "  docker-compose logs -f        # View logs"
echo "  docker-compose exec app bash  # Access container"
echo ""
