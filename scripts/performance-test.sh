#!/bin/bash

# Exit on error
set -e

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Configuration
SITE_URL="https://your-site.com"
CONCURRENT_USERS=50
TOTAL_REQUESTS=1000
TEST_DURATION=300
RESULTS_DIR="performance_results"
DATE=$(date +%Y%m%d_%H%M%S)

# Create results directory
mkdir -p "$RESULTS_DIR"

echo "Starting performance testing..."

# Function to check response time
check_response_time() {
    local url=$1
    local start_time=$(date +%s.%N)
    curl -s -o /dev/null -w "%{http_code}" "$url"
    local end_time=$(date +%s.%N)
    local duration=$(echo "$end_time - $start_time" | bc)
    echo "$duration"
}

# Function to check memory usage
check_memory_usage() {
    ps aux | grep php-fpm | grep -v grep | awk '{sum += $6} END {print sum/1024 " MB"}'
}

# Function to check CPU usage
check_cpu_usage() {
    ps aux | grep php-fpm | grep -v grep | awk '{sum += $3} END {print sum "%"}'
}

# Run Apache Benchmark test
echo "Running Apache Benchmark test..."
ab -n $TOTAL_REQUESTS -c $CONCURRENT_USERS -g "$RESULTS_DIR/ab_results_$DATE.tsv" "$SITE_URL/" > "$RESULTS_DIR/ab_summary_$DATE.txt"

# Run custom performance tests
echo "Running custom performance tests..."
{
    echo "=== Performance Test Results ($(date)) ==="
    echo "=== Response Time Tests ==="

    # Test homepage
    echo "Homepage response time: $(check_response_time "$SITE_URL") seconds"

    # Test article page
    echo "Article page response time: $(check_response_time "$SITE_URL/node/1") seconds"

    # Test search page
    echo "Search page response time: $(check_response_time "$SITE_URL/search") seconds"

    echo -e "\n=== Resource Usage ==="
    echo "Memory usage: $(check_memory_usage)"
    echo "CPU usage: $(check_cpu_usage)"

    echo -e "\n=== Database Queries ==="
    drush sql-query "SHOW GLOBAL STATUS LIKE 'Questions'" | grep Questions

    echo -e "\n=== Cache Statistics ==="
    drush cache:rebuild
    drush cache:stats

    echo -e "\n=== PHP-FPM Status ==="
    curl -s http://localhost/status | grep -E "active processes|total processes"

} > "$RESULTS_DIR/custom_tests_$DATE.txt"

# Generate performance report
echo "Generating performance report..."
{
    echo "=== Performance Test Report ($(date)) ==="
    echo -e "\n=== Apache Benchmark Summary ==="
    grep -E "Requests per second|Time per request|Failed requests" "$RESULTS_DIR/ab_summary_$DATE.txt"

    echo -e "\n=== Custom Tests Summary ==="
    cat "$RESULTS_DIR/custom_tests_$DATE.txt"

    echo -e "\n=== Recommendations ==="
    echo "1. Review Apache Benchmark results for response time and throughput"
    echo "2. Check memory and CPU usage patterns"
    echo "3. Analyze database query performance"
    echo "4. Review cache hit rates"
    echo "5. Consider implementing additional caching if needed"

} > "$RESULTS_DIR/performance_report_$DATE.txt"

# Create performance graphs (requires gnuplot)
if command -v gnuplot &> /dev/null; then
    echo "Generating performance graphs..."
    gnuplot << EOF
    set terminal png
    set output "$RESULTS_DIR/response_times_$DATE.png"
    set title "Response Times Distribution"
    set xlabel "Response Time (ms)"
    set ylabel "Number of Requests"
    plot "$RESULTS_DIR/ab_results_$DATE.tsv" using 5:1 with lines title "Response Time"
EOF
fi

echo -e "${GREEN}Performance testing completed!${NC}"
echo "Results are available in the $RESULTS_DIR directory:"
echo "1. Apache Benchmark results: ab_summary_$DATE.txt"
echo "2. Custom tests: custom_tests_$DATE.txt"
echo "3. Performance report: performance_report_$DATE.txt"
if [ -f "$RESULTS_DIR/response_times_$DATE.png" ]; then
    echo "4. Response time graph: response_times_$DATE.png"
fi

# Optional: Send results via email (uncomment and configure as needed)
# echo "Sending results via email..."
# mail -s "Performance Test Results" your-email@example.com < "$RESULTS_DIR/performance_report_$DATE.txt"