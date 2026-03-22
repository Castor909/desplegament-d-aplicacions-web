# Parte Primera

## 1. Creación de la carpeta y archivos

Se creó la carpeta `ficheros` dentro de `/var/www/sitio1` y se añadieron los archivos requeridos.

**Directorio `/var/www/sitio1` mostrando la carpeta `ficheros`:**  
![Directorio sitio1](./capturas/punto-1-1.png)

**Contenido de la carpeta `/var/www/sitio1/ficheros` con los archivos de texto creados:**  
![Archivos en ficheros](./capturas/punto-1-2.png)

---

## 2. Configuración del VirtualHost para autenticación

Se configuró el VirtualHost para proteger la carpeta `ficheros` mediante autenticación básica.

**Fragmento del archivo de configuración con la directiva `<Directory>` para la autenticación:**  
![Configuración de VirtualHost](./capturas/punto-2.png)

---

## 3. Creación del archivo de claves

Se generó el archivo `claves.txt` en `/etc/httpd` para almacenar las credenciales de los usuarios.

**Archivo `claves.txt` presente en `/etc/httpd`:**  
![Archivo claves.txt](./capturas/punto-3.png)

**Contenido de `claves.txt` con tres usuarios añadidos:**  
![Usuarios en claves.txt](./capturas/punto-4-1.png)

---

## 4. Gestión de usuarios en el archivo de claves

Se eliminó un usuario del archivo de claves y se comprobó el resultado.

**Contenido de `claves.txt` después de eliminar un usuario:**  
![claves.txt tras eliminación](./capturas/punto-4-2.png)

---

## 5. Recarga de Apache y acceso autenticado

Se recargó el servicio Apache y se accedió a la carpeta protegida desde el navegador.

**Estado del servicio Apache tras la recarga:**  
![Estado de Apache](./capturas/punto-5-1.png)

**Solicitud de autenticación al acceder a `/ficheros/`:**  
![Solicitud de login](./capturas/punto-5-2.png)

**Acceso exitoso mostrando el listado de archivos protegidos:**  
![Listado de archivos tras autenticación](./capturas/punto-5-3.png)

---
