FROM php:8.3-apache

# Instalar dependencias del sistema y driver ODBC de Microsoft
RUN apt-get update && apt-get install -y \
    gnupg2 \
    curl \
    apt-transport-https \
    ca-certificates \
    unixodbc \
    unixodbc-dev \
    libgssapi-krb5-2 \
    && curl -fsSL https://packages.microsoft.com/keys/microsoft.asc \
        | gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg \
    && echo "deb [arch=amd64 signed-by=/usr/share/keyrings/microsoft-prod.gpg] https://packages.microsoft.com/debian/12/prod bookworm main" \
        > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y msodbcsql18 \
    && pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Activar mod_rewrite por si usas .htaccess
RUN a2enmod rewrite

# Copiar proyecto al servidor Apache
COPY . /var/www/html/

# Permisos básicos
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
