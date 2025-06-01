#!/bin/bash

# Exit on error
set -e

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

echo "Starting security hardening..."

# Install security tools
echo "Installing security tools..."
sudo apt-get update
sudo apt-get install -y fail2ban ufw rkhunter

# Configure UFW (Uncomplicated Firewall)
echo "Configuring firewall..."
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow http
sudo ufw allow https
sudo ufw --force enable

# Configure Fail2Ban
echo "Configuring Fail2Ban..."
sudo tee /etc/fail2ban/jail.local << EOF
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 3

[sshd]
enabled = true
port = ssh
filter = sshd
logpath = /var/log/auth.log
maxretry = 3
bantime = 3600

[apache]
enabled = true
port = http,https
filter = apache-auth
logpath = /var/log/apache2/error.log
maxretry = 3
bantime = 3600
EOF

# Start and enable Fail2Ban
sudo systemctl enable fail2ban
sudo systemctl start fail2ban

# Configure PHP security settings
echo "Hardening PHP configuration..."
sudo tee /etc/php/8.2/fpm/conf.d/99-security.ini << EOF
expose_php = Off
display_errors = Off
log_errors = On
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
allow_url_fopen = Off
allow_url_include = Off
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_only_cookies = 1
session.cookie_samesite = "Strict"
EOF

# Configure Apache security headers
echo "Configuring Apache security headers..."
sudo tee /etc/apache2/conf-available/security-headers.conf << EOF
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"
Header set Referrer-Policy "strict-origin-when-cross-origin"
Header set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:;"
Header set Strict-Transport-Security "max-age=31536000; includeSubDomains"
EOF

# Enable security headers
sudo a2enconf security-headers

# Configure Drupal security settings
echo "Configuring Drupal security settings..."
drush config-set system.performance css.preprocess 1 -y
drush config-set system.performance js.preprocess 1 -y
drush config-set system.performance cache.page.max_age 3600 -y
drush config-set system.performance cache.page.use_internal 1 -y
drush config-set system.performance cache.page.use_internal_compression 1 -y

# Set secure file permissions
echo "Setting secure file permissions..."
sudo find /var/www/html -type d -exec chmod 755 {} \;
sudo find /var/www/html -type f -exec chmod 644 {} \;
sudo chmod 440 /var/www/html/sites/default/settings.php
sudo chmod 440 /var/www/html/sites/default/services.yml

# Install and configure RKHunter
echo "Configuring RKHunter..."
sudo rkhunter --update
sudo rkhunter --propupd
sudo rkhunter --check

# Restart services
echo "Restarting services..."
sudo systemctl restart php8.2-fpm
sudo systemctl restart apache2

echo -e "${GREEN}Security hardening complete!${NC}"
echo "Please review the following:"
echo "1. Check Fail2Ban status: sudo fail2ban-client status"
echo "2. Review RKHunter scan results"
echo "3. Test your website's security headers at https://securityheaders.com"
echo "4. Run a security scan with OWASP ZAP or similar tool"