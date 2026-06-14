FROM php:8.2-apache

# Copia la web al directorio que sirve Apache
COPY index.php /var/www/html/

# Expone el puerto 80 del contenedor
EXPOSE 80

# Comando de inicio: Apache en primer plano
CMD ["apache2-foreground"]
