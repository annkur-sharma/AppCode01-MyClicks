FROM php:8.1-fpm-alpine

RUN apk add --no-cache nginx bash jpegoptim pngquant libjpeg-turbo-utils util-linux
RUN docker-php-ext-install gd

WORKDIR /app
COPY . /app
COPY nginx.conf /etc/nginx/nginx.conf
RUN chmod +x /app/start.sh

EXPOSE 80
CMD ["/app/start.sh"]