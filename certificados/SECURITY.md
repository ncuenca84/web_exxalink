# Guía de seguridad — Sistema de certificados Exxalink S.A.S.

Este documento describe la puesta en marcha segura del sistema tras el
endurecimiento (Fase 1) y, sobre todo, la **rotación obligatoria de
credenciales**.

---

## 🔴 1. Rotación obligatoria de credenciales (hacer PRIMERO)

Las credenciales anteriores estuvieron en texto plano dentro del código y del
antiguo `README.txt`, por lo que **deben considerarse comprometidas**. Aunque
ya se eliminaron del proyecto, siguen existiendo en el historial del
repositorio y en el `web.zip` original. Es imprescindible cambiarlas:

1. **Contraseña de MySQL** del usuario `exxa_certificados`
   (cPanel → *Bases de datos MySQL* → *Usuarios actuales* → cambiar contraseña).
2. **Clave del panel de administración** (ver paso 3 más abajo — ahora se
   guarda como hash en la base de datos, ya no en el código).

---

## 2. Configuración (`config.php`)

El archivo `config.php` **no se versiona** (está en `.gitignore`). Debes
crearlo en el servidor a partir de la plantilla:

```bash
cd /home/exxa/public_html/certificados
cp config.example.php config.php
# Edita config.php y coloca la contraseña de MySQL ya rotada.
```

---

## 3. Crear el usuario administrador

El login ya no usa credenciales fijas en el código, sino la tabla `usuarios`
con contraseñas cifradas (`password_hash`).

> La base `exxa_certificados` y la tabla `certificados` **ya existen con sus
> datos**: no hay que recrearlas. Lo único nuevo es la tabla `usuarios`.

```bash
# 1) Crear SOLO la tabla nueva de usuarios (no toca `certificados`):
mysql -u exxa_certificados -p exxa_certificados < db/usuarios.sql

# 2) Crear el administrador (contraseña de 10+ caracteres):
php tools/crear_admin.php admin 'UNA_CLAVE_FUERTE_NUEVA'
```

`crear_admin.php` solo se ejecuta por línea de comandos (bloqueado por web).
(`db/schema.sql` contiene el esquema completo por referencia y es idempotente,
pero para una base ya en marcha basta con `db/usuarios.sql`.)

---

## 4. Qué se corrigió en la Fase 1

| Área | Antes | Ahora |
|---|---|---|
| Credenciales BD | En texto plano en `conexion.php` y `README.txt` | En `config.php` (fuera de git) |
| Login admin | Usuario/clave fijos en el código | Tabla `usuarios` con `password_hash()` |
| XSS | Datos impresos sin escapar | Todo escapado con `htmlspecialchars` |
| Códigos de certificado | `rand(1000,9999)` (adivinables) | `random_bytes` (no enumerables) |
| Generación de QR | Enviaba cada URL a una API externa | phpqrcode local (sin dependencias externas) |
| CSRF | Sin protección | Token CSRF en todos los formularios |
| Sesión | Sin regeneración; cookies sin flags | `session_regenerate_id`; HttpOnly/Secure/SameSite |
| Fuerza bruta | Sin límite | Bloqueo tras 5 intentos (15 min) |
| Archivos sensibles | `README.txt`, `.sql`, `error_log` accesibles | Eliminados / bloqueados por `.htaccess` |
| Errores | `die()` mostraba detalles internos | Mensajes genéricos + log interno |

---

## 5. Recomendación pendiente

- **Migrar de PHP 7.2** (fin de vida, sin parches de seguridad) a **PHP 8.1+**
  desde el *MultiPHP Manager* de cPanel. El código ya es compatible.
