#!/bin/bash

# Exit on error
set -e

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

# Configuration
BACKUP_DIR="/var/backups/drupal"
DATE=$(date +%Y%m%d_%H%M%S)
DRUPAL_ROOT="/var/www/html"
DB_NAME="drupal"
DB_USER="drupal"
DB_PASS="your_db_password"
RETENTION_DAYS=30

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

echo "Starting backup process..."

# Backup database
echo "Backing up database..."
mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > "$BACKUP_DIR/db_backup_$DATE.sql.gz"

# Backup files
echo "Backing up files..."
tar -czf "$BACKUP_DIR/files_backup_$DATE.tar.gz" \
    --exclude="$DRUPAL_ROOT/sites/default/files/tmp" \
    --exclude="$DRUPAL_ROOT/sites/default/files/css" \
    --exclude="$DRUPAL_ROOT/sites/default/files/js" \
    --exclude="$DRUPAL_ROOT/sites/default/files/php" \
    "$DRUPAL_ROOT/sites/default/files"

# Backup code
echo "Backing up code..."
tar -czf "$BACKUP_DIR/code_backup_$DATE.tar.gz" \
    --exclude="$DRUPAL_ROOT/sites/default/files" \
    --exclude="$DRUPAL_ROOT/sites/default/private" \
    --exclude="$DRUPAL_ROOT/sites/default/tmp" \
    "$DRUPAL_ROOT"

# Backup configuration
echo "Backing up configuration..."
drush config-export --destination="$BACKUP_DIR/config_$DATE"

# Create backup manifest
echo "Creating backup manifest..."
cat > "$BACKUP_DIR/backup_manifest_$DATE.txt" << EOF
Backup created: $(date)
Database: db_backup_$DATE.sql.gz
Files: files_backup_$DATE.tar.gz
Code: code_backup_$DATE.tar.gz
Configuration: config_$DATE/
EOF

# Create backup archive
echo "Creating backup archive..."
tar -czf "$BACKUP_DIR/full_backup_$DATE.tar.gz" \
    "$BACKUP_DIR/db_backup_$DATE.sql.gz" \
    "$BACKUP_DIR/files_backup_$DATE.tar.gz" \
    "$BACKUP_DIR/code_backup_$DATE.tar.gz" \
    "$BACKUP_DIR/config_$DATE" \
    "$BACKUP_DIR/backup_manifest_$DATE.txt"

# Clean up individual backup files
echo "Cleaning up individual backup files..."
rm "$BACKUP_DIR/db_backup_$DATE.sql.gz"
rm "$BACKUP_DIR/files_backup_$DATE.tar.gz"
rm "$BACKUP_DIR/code_backup_$DATE.tar.gz"
rm -rf "$BACKUP_DIR/config_$DATE"
rm "$BACKUP_DIR/backup_manifest_$DATE.txt"

# Remove old backups
echo "Removing backups older than $RETENTION_DAYS days..."
find "$BACKUP_DIR" -name "full_backup_*.tar.gz" -mtime +$RETENTION_DAYS -delete

# Verify backup
echo "Verifying backup..."
if [ -f "$BACKUP_DIR/full_backup_$DATE.tar.gz" ]; then
    echo -e "${GREEN}Backup completed successfully!${NC}"
    echo "Backup file: $BACKUP_DIR/full_backup_$DATE.tar.gz"
    echo "Backup size: $(du -h "$BACKUP_DIR/full_backup_$DATE.tar.gz" | cut -f1)"
else
    echo -e "${RED}Backup failed!${NC}"
    exit 1
fi

# Optional: Upload to remote storage (uncomment and configure as needed)
# echo "Uploading backup to remote storage..."
# aws s3 cp "$BACKUP_DIR/full_backup_$DATE.tar.gz" "s3://your-bucket/backups/"

echo "Backup process completed!"