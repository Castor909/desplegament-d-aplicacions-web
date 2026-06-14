## 6. Seguridad y persistencia (evidencias)

**Estado del contenedor y del firewall** (`docker compose ps` + `ufw status verbose`):

![Estado del contenedor y firewall](captures/06-2-estado-firewall.png)

**Ubicación de los datos persistentes** — `docker inspect` muestra el bind-mount
`/home/castor909/gitea/gitea -> /data`; ahí se guardan todos los datos de Gitea:

![Datos persistentes (bind-mount)](captures/06-1-persistencia.png)

### Tabla resumen final

| Usuario | Rol | Repositorio | Visibilidad | URL del servicio | Puertos |
|---|---|---|---|---|---|
| Castor909 | Administrador | — | — | http://192.168.1.178:3000 | 3000 web · 2222 SSH Git |
| student_one | Usuario normal | practica-gitea | **Privado** | http://192.168.1.178:3000/student_one/practica-gitea | 3000 web · 2222 SSH Git |

**Puertos:** `3000/TCP` interfaz web de Gitea; `2222/TCP` acceso SSH de Git.
**Persistencia:** los datos viven en el host en `~/gitea/gitea` (montado en `/data`
del contenedor). Si se borra esa carpeta se pierden usuarios, repositorios y
configuración. **Copia de seguridad:** `tar -czf backup-gitea.tar.gz ~/gitea/gitea`.

---

## BONUS — Acceso por SSH con clave pública

> **Nota:** este apartado se ha rehecho sobre la misma VM y el mismo Gitea, en la
> red doméstica actual, por lo que la IP del servidor aparece como `192.168.1.178`
> (en los apartados anteriores era `172.16.133.21`, otra red). El servicio, el
> repositorio y el usuario son los mismos.

En el primer intento el clonado por SSH fallaba con `Permission denied (publickey)`
porque el cliente no presentaba la clave correcta. Se solucionó añadiendo en
`~/.ssh/config` un alias que apunta a `git@192.168.1.178:2222` con la clave
`id_ed25519_gitea` (la clave pública ya estaba asociada al usuario `student_one`).

**Clave y autenticación (puerto 2222, usuario student_one):**

![Configuración SSH y autenticación correcta](captures/bonus-1-ssh-ok.png)

**Clonado del repositorio por SSH:**

![Clonado por SSH](captures/bonus-2-clone-ssh.png)

**Cambio, commit y push por SSH (sin usuario ni contraseña):**

![Push por SSH](captures/bonus-3-push-ssh.png)

**Confirmación en Gitea del commit subido mediante SSH:**

![Commit por SSH en Gitea](captures/bonus-4-gitea-commit.png)
