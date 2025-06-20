#!/bin/bash

# Setup CRON job script for XKCD email system
# This script automatically configures a CRON job to run cron.php every 24 hours

# Get the current directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CRON_PHP_PATH="$SCRIPT_DIR/cron.php"

# Create a temporary cron file
TEMP_CRON_FILE="/tmp/xkcd_cron_temp"

# Get current crontab (if any)
crontab -l > "$TEMP_CRON_FILE" 2>/dev/null || echo "" > "$TEMP_CRON_FILE"

# Check if our cron job already exists
if grep -q "cron.php" "$TEMP_CRON_FILE"; then
    echo "CRON job for XKCD already exists. Updating..."
    # Remove existing XKCD cron job
    grep -v "cron.php" "$TEMP_CRON_FILE" > "${TEMP_CRON_FILE}.new"
    mv "${TEMP_CRON_FILE}.new" "$TEMP_CRON_FILE"
fi

# Add new cron job (runs every 24 hours at 9:00 AM)
echo "0 9 * * * /usr/bin/php $CRON_PHP_PATH >> $SCRIPT_DIR/cron.log 2>&1" >> "$TEMP_CRON_FILE"

# Install the new crontab
crontab "$TEMP_CRON_FILE"

# Clean up
rm "$TEMP_CRON_FILE"

# Verify the cron job was added
echo "CRON job setup completed!"
echo "Current crontab entries:"
crontab -l | grep -E "(cron.php|XKCD)"

# Make sure the cron.php file is executable
chmod +x "$CRON_PHP_PATH"

# Create log file if it doesn't exist
LOG_FILE="$SCRIPT_DIR/cron.log"
touch "$LOG_FILE"
chmod 666 "$LOG_FILE"

echo ""
echo "CRON job has been set up to run daily at 9:00 AM"
echo "Log file: $LOG_FILE"
echo "PHP script: $CRON_PHP_PATH"
echo ""
echo "To manually test the cron job, run:"
echo "php $CRON_PHP_PATH"