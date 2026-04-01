#!/bin/bash

echo "Deploying to production environment..."

docker compose -f docker-compose.prod.yml down
docker compose -f docker-compose.prod.yml build --no-cache
docker compose -f docker-compose.prod.yml up -d --build

echo "✅ Deployment completed successfully!"