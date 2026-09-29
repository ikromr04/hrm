# PHP 8.4 rather than an older one: spatie/laravel-activitylog asks for ^8.4,
# and the lock file pins Symfony components that need 8.4.1.
FROM mcr.microsoft.com/devcontainers/php:8.4

# Two extensions the image does not carry: exif is read by the media library
# when it stores a photograph, bcmath by one of its dependencies.
RUN docker-php-ext-install exif bcmath
