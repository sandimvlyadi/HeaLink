#!/bin/bash
set -e

echo "Deploying to production environment..."

# Step 1: Build new image while old containers are still serving traffic
echo "Building new image..."
docker compose -f docker-compose.prod.yml build --no-cache

# Step 2: Run migrations using the new image before swapping containers
echo "Running database migrations..."
docker compose -f docker-compose.prod.yml run --rm --no-deps app php artisan migrate --force

# Step 3: Swap containers with the new image (seconds of downtime, not minutes)
echo "Swapping containers..."
docker compose -f docker-compose.prod.yml up -d --remove-orphans

# Step 4: Reload Octane workers to pick up new code and schema (no full restart needed)
echo "Reloading Octane workers..."
docker compose -f docker-compose.prod.yml exec -T app php artisan octane:reload

# Step 5: Clean up dangling images from previous builds
docker image prune -f

echo "✅ Deployment completed successfully!"
