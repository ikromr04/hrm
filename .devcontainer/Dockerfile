# PHP 8.4 rather than an older one: spatie/laravel-activitylog asks for ^8.4,
# and the lock file pins Symfony components that need 8.4.1.
FROM mcr.microsoft.com/devcontainers/php:8.4

# Extensions the image does not carry. GD is not optional here: avatars and the
# previews of equipment photographs are cut with imagecreatetruecolor and
# imagejpeg, so without it every upload fails. Exif is asked for by the media
# library, bcmath by one of its dependencies.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" gd exif bcmath zip \
    && rm -rf /var/lib/apt/lists/*

# Xdebug arrives in step-debug mode: it prints a failed-connection notice before
# every command and makes anything long, like seeding, crawl. Off by default —
# a session that wants the debugger runs with XDEBUG_MODE=debug.
RUN echo 'xdebug.mode=off' > /usr/local/etc/php/conf.d/zz-xdebug-off.ini
