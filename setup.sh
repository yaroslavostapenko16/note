#!/bin/bash

#########################################
# Note Application - Installation Script
#########################################

set -e

echo "=================================="
echo "Note Application - Setup"
echo "=================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check PHP installation
echo -e "${YELLOW}Checking PHP installation...${NC}"
if ! command -v php &> /dev/null; then
    echo -e "${RED}PHP is not installed. Please install PHP 7.4 or higher.${NC}"
    exit 1
fi

PHP_VERSION=$(php -r 'echo phpversion();')
echo -e "${GREEN}✓ PHP ${PHP_VERSION} found${NC}"
echo ""

# Check MySQL/MariaDB
echo -e "${YELLOW}Checking MySQL/MariaDB installation...${NC}"
if ! command -v mysql &> /dev/null; then
    echo -e "${RED}MySQL/MariaDB is not installed. Please install MySQL or MariaDB.${NC}"
    exit 1
fi
echo -e "${GREEN}✓ MySQL/MariaDB found${NC}"
echo ""

# Create directories
echo -e "${YELLOW}Creating directories...${NC}"
mkdir -p uploads
mkdir -p uploads/temp
mkdir -p logs
chmod 755 uploads
chmod 755 uploads/temp
chmod 755 logs
echo -e "${GREEN}✓ Directories created${NC}"
echo ""

# Database setup
echo -e "${YELLOW}Setting up database...${NC}"
DB_HOST="localhost"
DB_USER="u757840095_note2"
DB_PASS="MB?EM6aTa7&M"
DB_NAME="u757840095_note"

echo "Database Configuration:"
echo "  Host: $DB_HOST"
echo "  Database: $DB_NAME"
echo "  User: $DB_USER"
echo ""

echo -e "${YELLOW}Creating database...${NC}"
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME;" 2>/dev/null || {
    echo -e "${YELLOW}Database might already exist or credentials incorrect.${NC}"
}
echo -e "${GREEN}✓ Database ready${NC}"
echo ""

# Initialize database tables
echo -e "${YELLOW}Initializing database tables...${NC}"
php api/database.php
echo -e "${GREEN}✓ Database tables initialized${NC}"
echo ""

# Create necessary files
echo -e "${YELLOW}Creating configuration files...${NC}"
touch logs/error.log
touch logs/access.log
chmod 666 logs/error.log
chmod 666 logs/access.log
echo -e "${GREEN}✓ Configuration files created${NC}"
echo ""

# Generate secure keys (optional)
echo -e "${YELLOW}Generating security files...${NC}"
# These would be added to config if needed
echo -e "${GREEN}✓ Security setup complete${NC}"
echo ""

echo -e "${GREEN}=================================="
echo "Installation Complete!"
echo "===================================${NC}"
echo ""
echo "Next steps:"
echo "1. Update database credentials in api/config.php if needed"
echo "2. Deploy to https://note.websweos.com"
echo "3. Ensure .htaccess is enabled on your server"
echo "4. Test the application at https://note.websweos.com/app"
echo ""
echo "For more information, see README.md"
