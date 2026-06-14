FROM php:8.2-apache

# Extensión mysqli para conectar con MySQL desde PHP
RUN docker-php-ext-install mysqli

# Copia la web al directorio que sirve Apache
COPY index.php /var/www/html/
COPY db_test.php /var/www/html/

# Expone el puerto 80 del contenedor
EXPOSE 80

# Comando de inicio: Apache en primer plano
CMD ["apache2-foreground"]
