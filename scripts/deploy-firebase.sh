#!/bin/bash

# Exit on error
set -e

# Install Firebase CLI if not installed
if ! command -v firebase &> /dev/null; then
    npm install -g firebase-tools
fi

# Login to Firebase
firebase login

# Initialize Firebase (if not already initialized)
if [ ! -f "firebase.json" ]; then
    firebase init hosting
fi

# Build the project
composer install --no-dev --optimize-autoloader
drush cr

# Deploy to Firebase
firebase deploy --only hosting