#!/bin/bash

# Exit on error
set -e

# Colors for output
GREEN='\033[0;32m'
NC='\033[0m'

echo "Setting up comprehensive monitoring..."

# Install New Relic PHP agent
if [ ! -f "/usr/local/newrelic-php5-common/scripts/newrelic.ini" ]; then
    echo "Installing New Relic PHP agent..."
    curl -L https://download.newrelic.com/php_agent/release/newrelic-php5-common_10.0.0_all.deb -o newrelic.deb
    sudo dpkg -i newrelic.deb
    rm newrelic.deb
fi

# Configure New Relic
echo "Configuring New Relic..."
sudo sed -i "s/newrelic.appname = \"PHP Application\"/newrelic.appname = \"Alter-Consult\"/" /usr/local/newrelic-php5-common/scripts/newrelic.ini
sudo sed -i "s/newrelic.license = \"\"/newrelic.license = \"$NEW_RELIC_LICENSE_KEY\"/" /usr/local/newrelic-php5-common/scripts/newrelic.ini

# Add New Relic configuration to PHP
echo "Adding New Relic to PHP configuration..."
echo "extension=newrelic.so" | sudo tee -a /etc/php/8.2/fpm/conf.d/20-newrelic.ini
echo "extension=newrelic.so" | sudo tee -a /etc/php/8.2/cli/conf.d/20-newrelic.ini

# Install and configure Prometheus Node Exporter
echo "Installing Prometheus Node Exporter..."
curl -L https://github.com/prometheus/node_exporter/releases/download/v1.3.1/node_exporter-1.3.1.linux-amd64.tar.gz -o node_exporter.tar.gz
tar xvfz node_exporter.tar.gz
sudo mv node_exporter-1.3.1.linux-amd64/node_exporter /usr/local/bin/
rm -rf node_exporter*

# Create systemd service for Node Exporter
echo "Creating Node Exporter service..."
sudo tee /etc/systemd/system/node_exporter.service << EOF
[Unit]
Description=Node Exporter
After=network-online.target

[Service]
User=node_exporter
Group=node_exporter
Type=simple
ExecStart=/usr/local/bin/node_exporter

[Install]
WantedBy=multi-user.target
EOF

# Install and configure Grafana
echo "Installing Grafana..."
wget -q -O - https://packages.grafana.com/gpg.key | sudo apt-key add -
echo "deb https://packages.grafana.com/oss/deb stable main" | sudo tee /etc/apt/sources.list.d/grafana.list
sudo apt-get update
sudo apt-get install -y grafana

# Configure Grafana
sudo tee /etc/grafana/provisioning/datasources/prometheus.yml << EOF
apiVersion: 1
datasources:
  - name: Prometheus
    type: prometheus
    access: proxy
    url: http://localhost:9090
    isDefault: true
EOF

# Start and enable services
echo "Starting monitoring services..."
sudo systemctl daemon-reload
sudo systemctl enable node_exporter
sudo systemctl start node_exporter
sudo systemctl enable grafana-server
sudo systemctl start grafana-server

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm

echo -e "${GREEN}Monitoring setup complete!${NC}"
echo "Access Grafana at http://localhost:3000"
echo "Access Node Exporter metrics at http://localhost:9100/metrics"