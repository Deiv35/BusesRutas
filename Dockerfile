FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    unixodbc \
    unixodbc-dev \
    curl \
    gnupg2 \
    apt-transport-https \
    ca-certificates

# Clave Microsoft (moderna)
RUN curl https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor -o /usr/share/keyrings/microsoft.gpg

# Repo
RUN echo "deb [signed-by=/usr/share/keyrings/microsoft.gpg] https://packages.microsoft.com/debian/11/prod bullseye main" > /etc/apt/sources.list.d/mssql-release.list

# Instalar ODBC
RUN apt-get update && ACCEPT_EULA=Y apt-get install -y msodbcsql17

# Activar PDO ODBC (clave 🔥)
RUN docker-php-ext-install pdo_odbc

# Apache
RUN a2enmod rewrite

COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
