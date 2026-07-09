# Use an official lightweight PHP image with Apache
FROM php:8.2-apache

# Copy all your local files (index.html, send_result.php) into the web directory
COPY . /var/www/html/

# Expose port 80 for web traffic
EXPOSE 80
