# Servidor para publicar el sistema en Render: PHP con Apache
FROM php:8.3-apache

# mysqli para conectarse a MySQL (curl ya viene incluido)
RUN docker-php-ext-install mysqli

# Hora de Guatemala para las fechas de PHP y no mostrar errores internos a los visitantes
RUN echo "date.timezone=America/Guatemala" > /usr/local/etc/php/conf.d/zona-horaria.ini \
    && echo "display_errors=Off" > /usr/local/etc/php/conf.d/errores.ini

# Render indica en PORT el puerto donde debe escuchar el servidor
ENV PORT=10000
RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf \
    && sed -i 's/:80>/:${PORT}>/' /etc/apache2/sites-available/000-default.conf

COPY . /var/www/html/

# conexion.php y ca.pem se suben a Render como "Secret Files" y Render los deja
# en la raíz del proyecto al construir. Se mueven a config/ para que Apache pueda leerlos.
RUN mkdir -p /var/www/html/config \
    && mv /var/www/html/conexion.php /var/www/html/ca.pem /var/www/html/config/ \
    && chown -R www-data:www-data /var/www/html/config \
    && rm -rf /var/www/html/postman
