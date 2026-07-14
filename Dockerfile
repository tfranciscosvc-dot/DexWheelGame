# Use an official lightweight PHP image with Apache
FROM php:8.2-apache

# Install the PostgreSQL development libraries and the PHP PDO PostgreSQL extensions
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Copy all your local files into the web directory
COPY . /var/www/html/

# Tell Apache to prioritize index.php as the homepage
RUN echo "DirectoryIndex index.php index.html" >> /var/www/html/.htaccess

# Expose port 80 for web traffic
EXPOSE 80
