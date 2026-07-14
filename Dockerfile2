# Use an official lightweight PHP image with Apache
FROM php:8.2-apache

# Copy all your local files into the web directory
COPY . /var/www/html/

# Tell Apache to prioritize index.php as the homepage
RUN echo "DirectoryIndex index.php index.html" >> /var/www/html/.htaccess

# Expose port 80 for web traffic
EXPOSE 80
