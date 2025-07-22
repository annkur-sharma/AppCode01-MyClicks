### STAGE 1: Build PHP with GD support ###
FROM php:8.1-fpm-alpine AS builder

RUN apk add --no-cache \
    zlib-dev libpng-dev freetype-dev libjpeg-turbo-dev \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS

RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    && docker-php-ext-install gd

# Save PHP extension directory path
RUN echo $(php -r "echo ini_get('extension_dir');") > /ext-dir.txt

### STAGE 2: Final Image ###
FROM php:8.1-fpm-alpine

# Install runtime dependencies
RUN apk add --no-cache \
    nginx bash jpegoptim pngquant libjpeg-turbo-utils util-linux \
    freetype libjpeg-turbo zlib libpng

# Copy GD extension and PHP config from builder
COPY --from=builder /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=builder /usr/local/include/php /usr/local/include/php
COPY --from=builder /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d

# Setup app
WORKDIR /app
COPY . /app
COPY nginx.conf /etc/nginx/nginx.conf
COPY start.sh /app/start.sh
RUN chmod +x /app/start.sh
RUN ls -l /app/

EXPOSE 80
# Remove default entrypoint
ENTRYPOINT []

CMD ["/bin/sh", "/app/start.sh"]
