FROM php:8-apache
RUN apt-get update && apt-get install -yqq libldap2-dev libldap-common
RUN docker-php-ext-configure ldap
RUN docker-php-ext-install ldap
RUN a2enmod rewrite
COPY .htaccess /var/www/html/.htaccess
COPY api.php /var/www/html/api.php