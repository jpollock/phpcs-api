#!/bin/bash

# Create necessary directories if they don't exist
mkdir -p logs data

# Check if api_keys.json exists, create it if not
if [ ! -f data/api_keys.json ]; then
    echo '{"keys": {}}' > data/api_keys.json
fi

# Start the API server in the background
echo "Starting API server on http://localhost:8080"
php -S localhost:8080 -t public &
API_PID=$!

# Start the documentation server in the background
echo "Starting documentation server on http://localhost:8081"
echo "View OpenAPI documentation at http://localhost:8081/docs/openapi.html"
php -S localhost:8081 -t . &
DOCS_PID=$!

# Function to kill both servers on exit
function cleanup {
    echo "Stopping servers..."
    kill $API_PID
    kill $DOCS_PID
    exit 0
}

# Trap Ctrl+C and call cleanup
trap cleanup INT

# Keep the script running
echo "Press Ctrl+C to stop the servers"
wait
