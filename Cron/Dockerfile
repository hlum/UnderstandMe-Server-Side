FROM php:8.2-cli

# Install system deps + cron + git
RUN apt-get update \
    && apt-get install -y \
        cron \
        git \
        default-mysql-client \
    && docker-php-ext-install mysqli \
    && rm -rf /var/lib/apt/lists/*

# Copy crontab
COPY crontab /etc/cron.d/app-cron

# Set permissions and register cron
RUN chmod 0644 /etc/cron.d/app-cron \
    && crontab /etc/cron.d/app-cron

WORKDIR /var/www/html

CMD ["cron", "-f"]
