#!/bin/bash
set -e

# Wait for database to be ready
echo "Waiting for database to be ready..."
while ! nc -z db 3306; do
  sleep 1
done
echo "Database is ready!"

# Start Apache in foreground
exec "$@" 