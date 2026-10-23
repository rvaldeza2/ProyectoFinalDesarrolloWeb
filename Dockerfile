# Servidor para publicar el sistema en Render: PHP con Apache
FROM php:8.3-apache

# mysqli para conectarse a MySQL (curl ya viene incluido)
RUN docker-php-ext-install mysqli

# Hora de Guatemala para las fechas de PHP
RUN echo "date.timezone=America/Guatemala" > /usr/local/etc/php/conf.d/zona-horaria.ini

# Render indica en PORT el puerto donde debe escuchar el servidor
ENV PORT=10000
RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf \
    && sed -i 's/:80>/:${PORT}>/' /etc/apache2/sites-available/000-default.conf

COPY . /var/www/html/

# conexion.php y ca.pem se suben a Render como "Secret Files" y quedan en /etc/secrets.
# Render también los copia a la raíz del proyecto, así que se borran de ahí
# y la carpeta config apunta a /etc/secrets.
RUN rm -f /var/www/html/conexion.php /var/www/html/ca.pem \
    && rm -rf /var/www/html/config /var/www/html/postman \
    && ln -s /etc/secrets /var/www/html/config
