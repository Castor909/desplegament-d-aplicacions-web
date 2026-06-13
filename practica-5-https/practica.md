> **Nota sobre el entorno**
>
> El enunciado está redactado para **Ubuntu/Debian** (`/etc/apache2`, carpeta
> `sites-available`, utilidades `a2enmod` y `apache2ctl`). Esta práctica se ha
> realizado sobre **Arch Linux / CachyOS**, donde el mismo servidor (Apache HTTP
> Server) se distribuye con otra nomenclatura. Equivalencias aplicadas:
>
> | Ubuntu/Debian | Arch / CachyOS (usado aquí) |
> |---|---|
> | Paquete `apache2` (servicio `apache2`) | Paquete `apache`, binario `httpd`, servicio `httpd` |
> | `/etc/apache2/sites-available/` | `/etc/httpd/conf/extra/` |
> | Plantilla `default-ssl.conf` | `/etc/httpd/conf/extra/httpd-ssl.conf` |
> | `a2enmod ssl` | descomentar `LoadModule` en `httpd.conf` y verificar con `httpd -M` |
> | `apache2ctl -t` | `apachectl -t` |
> | `AuthUserFile /etc/apache2/...` | `AuthUserFile /etc/httpd/claves.txt` |
>
> El cliente (navegador) y el servidor están en la misma máquina, por lo que el
> tráfico capturado en Wireshark viaja por la interfaz de **loopback (`lo`)** y
> las direcciones de origen/destino son `127.0.0.1` / `::1`.

---

Partimos del `sitio1` ya creado y protegido con autenticación básica por grupos
en la carpeta `ficheros` (Práctica 4). El objetivo es migrar el sitio de HTTP a
HTTPS y demostrar con Wireshark que, antes del cambio, las credenciales viajan en
texto plano y, después, quedan cifradas.

---

## 1. Captura del tráfico HTTP y acceso a la carpeta protegida

Con Wireshark capturando en la interfaz **Loopback: lo**, se accede desde el
navegador a `http://www.sitio1.com/ficheros` y se introducen las credenciales de
un usuario con acceso (`usuario1`).

**Solicitud de login del navegador en `sitio1`:**  
![Solicitud de login HTTP](./capturas/punto-1-1-login.png)

**Contenido de la carpeta `ficheros` tras autenticarse:**  
![Listado de ficheros](./capturas/punto-1-2-ficheros.png)

Al detener la captura y filtrar por `http`, se localiza la trama
`GET /ficheros/ HTTP/1.1` (trama 136), precedida de la secuencia habitual de
autenticación básica: una primera petición sin credenciales que recibe
`401 Unauthorized`, y la repetición ya con la cabecera `Authorization`.

**Tráfico HTTP capturado con la trama `GET /ficheros/`:**  
![Tráfico HTTP capturado](./capturas/punto-2-credenciales.png)

---

## 2. Inspección de la trama: credenciales en texto plano

Inspeccionando el árbol **Hypertext Transfer Protocol** de la trama localizada se
observa la cabecera:

```
Authorization: Basic dXN1YXJpbzE6Y2xhdmUx
```

Wireshark decodifica directamente el valor Base64 y muestra:

```
Credentials: usuario1:clave1
```

Base64 **no es cifrado**, es una simple codificación reversible por cualquiera:

```bash
printf 'usuario1:clave1' | base64       # -> dXN1YXJpbzE6Y2xhdmUx
printf 'dXN1YXJpbzE6Y2xhdmUx' | base64 -d  # -> usuario1:clave1
```

Por tanto, sobre HTTP el usuario y la contraseña viajan **legibles** para
cualquiera que capture el tráfico (ver captura del punto 1).

---

## 3. Generación del certificado autofirmado con OpenSSL

En el directorio de configuración (`/etc/httpd/conf/extra/`, equivalente a
`sites-available`) se genera, en una única instrucción, la clave privada y el
certificado autofirmado:

```bash
cd /etc/httpd/conf/extra
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout sitio1.key -out sitio1.crt \
  -subj "/C=ES/ST=Illes Balears/L=Palma/O=CIFP FBMoll/CN=www.sitio1.com"
```

Cada opción cumple un requisito del enunciado:

| Opción | Requisito |
|---|---|
| `req -x509` | certificado autofirmado en formato **X.509** |
| `-nodes` | clave privada **sin** cifrado DES (sin passphrase) |
| `-days 365` | validez de **365 días** |
| `-newkey rsa:2048` | clave **RSA de 2048 bits** |
| `-keyout` / `-out` | nombres **sitio1.key** y **sitio1.crt** |

**Comando de creación de `.key` y `.crt` y su resultado:**  
![Comando openssl](./capturas/punto-3-1-openssl.png)

La verificación del certificado confirma los parámetros exigidos (`Version: 3`
→ X.509v3, `Public-Key: (2048 bit)`, validez de un año):

```bash
sudo openssl x509 -in sitio1.crt -noout -text
```

**Contenido del certificado (verificación con openssl):**  
![Contenido del certificado](./capturas/punto-3-2-cert.png)

**Contenido de la carpeta tras la creación (con `sitio1.key` y `sitio1.crt`):**  
![Carpeta tras creación](./capturas/punto-3-3-carpeta.png)

---

## 4. Activación del módulo SSL y reinicio

En Arch no existe `a2enmod`; el equivalente es descomentar las directivas
`LoadModule` en `httpd.conf`. Se activan **`ssl_module`** (HTTPS/TLS) y
**`socache_shmcb_module`** (caché de sesiones SSL que requiere la plantilla):

```bash
sudo sed -i 's|^#LoadModule ssl_module|LoadModule ssl_module|; s|^#LoadModule socache_shmcb_module|LoadModule socache_shmcb_module|' /etc/httpd/conf/httpd.conf
httpd -M | grep ssl
sudo systemctl restart httpd
```

`httpd -M` confirma que el módulo queda cargado (`ssl_module (shared)`).

**Activación del módulo SSL y reinicio de Apache:**  
![Activación del módulo SSL](./capturas/punto-4-modulo.png)

---

## 5. Configuración SSL en `sitio1.conf`

Tomando como base la plantilla `httpd-ssl.conf` (equivalente a `default-ssl.conf`)
se configura `sitio1.conf` para servir `sitio1` por HTTPS. **Importante:** se
conserva el bloque `<Directory>` de `ficheros` con la autenticación por grupos de
la Práctica 4. El VirtualHost pasa a escuchar en el puerto **443**, indicando la
clave y el certificado generados:

```apache
Listen 443

SSLSessionCache        "shmcb:/run/httpd/ssl_scache(512000)"
SSLSessionCacheTimeout 300

<VirtualHost *:443>
    ServerName www.sitio1.com
    ServerAlias sitio1.com
    DocumentRoot "/var/www/sitio1"

    SSLEngine on
    SSLCertificateFile    "/etc/httpd/conf/extra/sitio1.crt"
    SSLCertificateKeyFile "/etc/httpd/conf/extra/sitio1.key"

    SSLProtocol all -SSLv3
    SSLCipherSuite HIGH:MEDIUM:!MD5:!RC4:!3DES

    <Directory "/var/www/sitio1">
        AllowOverride All
        Require all granted
    </Directory>

    <Directory "/var/www/sitio1/ficheros">
        Options Indexes FollowSymLinks
        DirectoryIndex index.html
        AuthType Basic
        AuthName "Zona protegida"
        AuthUserFile /etc/httpd/claves.txt
        AuthGroupFile /etc/httpd/grupos.txt
        Require group grupoA grupoC
        AuthzSendForbiddenOnFailure On
    </Directory>

    ErrorLog "/var/log/httpd/sitio1-ssl-error_log"
    CustomLog "/var/log/httpd/sitio1-ssl-access_log" common
</VirtualHost>
```

Al sustituir el VirtualHost `:80` de `sitio1` por el de `:443`, `sitio1` deja de
publicarse por HTTP (lo que se comprueba en el punto 7).

**Fichero `sitio1.conf` con la configuración SSL y el `<Directory>` heredado:**  
![sitio1.conf con SSL](./capturas/punto-5-sitio1conf.png)

---

## 6. Comprobación de sintaxis y recarga

Se comprueba la sintaxis con `apachectl -t` (equivalente a `apache2ctl -t`) y se
aplica la configuración. Como se ha añadido un **nuevo puerto `Listen 443`**, una
recarga *graceful* no abriría el socket nuevo, por lo que se realiza un `restart`
para que Apache empiece a escuchar en el 443:

```bash
sudo apachectl -t
sudo systemctl restart httpd
```

La salida `Syntax OK` confirma que la configuración es válida.

**Comprobación de sintaxis y reinicio:**  
![apachectl -t y reinicio](./capturas/punto-6-sintaxis.png)

---

## 7. Acceso por HTTP: página por defecto

Al abrir `http://www.sitio1.com`, el navegador ya **no** muestra `sitio1`, sino la
**página por defecto** del servidor, porque `sitio1` ya no tiene VirtualHost en el
puerto 80.

**`http://www.sitio1.com` muestra la página por defecto:**  
![HTTP a página por defecto](./capturas/punto-7-http-defecto.png)

---

## 8. Acceso por HTTPS al sitio creado

Al acceder a `https://www.sitio1.com`, el navegador advierte de un riesgo de
seguridad porque el certificado es **autofirmado** (no lo emite una Autoridad de
Certificación reconocida). Es el comportamiento esperado.

**Aviso de seguridad por certificado autofirmado:**  
![Aviso de certificado](./capturas/punto-8-1-aviso.png)

Tras aceptar el riesgo (*Advanced → Accept the Risk and Continue*) se carga de
nuevo la página de `sitio1`, ahora servida por **HTTPS** (se aprecia `https://`
en la barra de direcciones).

**`https://www.sitio1.com` sirviendo `sitio1` por HTTPS:**  
![Sitio por HTTPS](./capturas/punto-8-2-https.png)

---

## 9. Captura del acceso a `ficheros` por HTTPS

Se inicia una nueva captura en Wireshark y se accede a
`https://www.sitio1.com/ficheros`, introduciendo de nuevo las credenciales de un
usuario válido. El acceso se realiza correctamente por HTTPS.

**Navegador en `https://www.sitio1.com/ficheros` tras acceder:**  
![ficheros por HTTPS](./capturas/punto-9-https-ficheros.png)

---

## 10. Verificación del cifrado: HTTP vacío, TLS cifrado

Al detener la captura y filtrar por `http`, **no aparece ninguna trama**
(`Displayed: 0`): nada se ha transmitido en claro, por lo que las credenciales no
pueden interceptarse.

**Filtro `http`: sin tramas capturadas:**  
![Filtro HTTP vacío](./capturas/punto-10-1-http-vacio.png)

Al filtrar por `tls` sí aparece el intercambio (`Client Hello`, `Server Hello`,
`Application Data`). Inspeccionando un paquete `Application Data` se observa
`Encrypted Application Data`: el contenido —incluidas las credenciales— viaja
**cifrado** entre cliente y servidor.

**Filtro `tls`: tráfico cifrado (TLS/SSL):**  
![Tráfico TLS cifrado](./capturas/punto-10-2-tls.png)

---

## Conclusión

El contraste entre el punto 2 (`Credentials: usuario1:clave1` legible sobre HTTP)
y el punto 10 (`Encrypted Application Data` sobre HTTPS) demuestra el objetivo de
la práctica: HTTPS cifra el canal de extremo a extremo, protegiendo las
credenciales de autenticación básica que en HTTP viajaban en texto plano.
