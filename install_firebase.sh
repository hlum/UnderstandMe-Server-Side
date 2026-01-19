#!/bin/bash

echo "🔥 Firebase Authentication Setup Script"
echo "========================================"
echo ""

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    echo "❌ Error: Composer is not installed"
    echo "Please install Composer first: https://getcomposer.org/download/"
    exit 1
fi

echo "✅ Composer found"
echo ""

# Check if we're in the right directory
if [ ! -f "composer.json" ]; then
    echo "❌ Error: composer.json not found"
    echo "Please run this script from the API directory:"
    echo "  cd /Users/cmstudent/School/卒制/API/sotsusei"
    echo "  bash install_firebase.sh"
    exit 1
fi

echo "✅ Found composer.json"
echo ""

# Check if service account file exists
if [ ! -f "config/service-account.json" ]; then
    echo "⚠️  Warning: config/service-account.json not found"
    echo "Firebase authentication will not work without this file"
    echo ""
    read -p "Do you want to continue anyway? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

echo "📦 Installing Firebase Admin SDK..."
echo ""

# Install dependencies
composer install

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Installation successful!"
    echo ""
    echo "📋 Next steps:"
    echo "1. Restart your web server:"
    echo "   - Docker: docker-compose restart"
    echo "   - PHP-FPM: sudo systemctl restart php-fpm"
    echo "   - Nginx: sudo service nginx restart"
    echo ""
    echo "2. Test the API with a Firebase token from your web app"
    echo ""
    echo "3. Check the logs if you encounter any errors:"
    echo "   - tail -f /var/log/nginx/error.log"
    echo ""
    echo "📖 For more information, see FIREBASE_AUTH_SETUP.md"
else
    echo ""
    echo "❌ Installation failed"
    echo "Check the error messages above and try again"
    exit 1
fi
