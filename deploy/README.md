# Despliegue por Git (push directo al servidor) — Exxalink S.A.S.

Flujo: haces `git push produccion main` desde tu equipo y el servidor publica
automáticamente el sitio en `/home/exxa/public_html`, restableciendo el
propietario `exxa` y los permisos. **Nunca toca `my.exxalink.com`** (no está
versionado, así que el hook jamás lo lista).

---

## 1. Configuración inicial en el servidor (una sola vez, como root)

```bash
# Crear el repo bare e instalar el hook
mkdir -p /home/exxa/repos
git init --bare /home/exxa/repos/web_exxalink.git
```

Copia el archivo `deploy/post-receive` de este repo al servidor en
`/home/exxa/repos/web_exxalink.git/hooks/post-receive` y hazlo ejecutable:

```bash
chmod +x /home/exxa/repos/web_exxalink.git/hooks/post-receive
chown -R exxa:exxa /home/exxa/repos
```

> Alternativa: sube la carpeta `deploy/` al servidor y ejecuta
> `bash deploy/setup-servidor.sh` (hace todo lo anterior).

### Limpieza única de archivos antiguos (importante)

El checkout de git **no elimina** ficheros que ya existan en `public_html` y
que no estén en el repo. Borra a mano los antiguos sensibles/muertos (si están):

```bash
cd /home/exxa/public_html
rm -f certificados/README.txt certificados/certificados.sql certificados/admin/error_log
rm -f css/style-darkblue.css css/style-green.css css/style-grey.css \
      css/style-purple.css css/style-rtl.css css/bootstrap-rtl.min.css js/jquery-ui.min.js
```

---

## 2. Conectar tu equipo local con el servidor

```bash
git remote add produccion ssh://root@TU_SERVIDOR:PUERTO/home/exxa/repos/web_exxalink.git
```

(Sustituye `TU_SERVIDOR` y `PUERTO`. Si tu SSH usa el puerto 22 estándar,
puedes usar la forma corta `root@TU_SERVIDOR:/home/exxa/repos/web_exxalink.git`.)

---

## 3. Publicar

```bash
git push produccion main
```

Cada push a `main` republica el sitio y corrige permisos automáticamente.

> El hook publica la rama **main**. Para desplegar estos cambios primero
> fusiónalos a main, o edita `DEPLOY_BRANCH` en el hook si prefieres publicar
> otra rama.

---

## 4. Primer despliegue: configurar el sistema de certificados

`config.php` no viaja por git (lleva credenciales). En el servidor:

```bash
cd /home/exxa/public_html/certificados
cp config.example.php config.php        # coloca la contraseña MySQL YA ROTADA
mysql -u exxa_certificados -p exxa_certificados < db/schema.sql
php tools/crear_admin.php admin 'UNA_CLAVE_FUERTE_NUEVA'
```

El hook ya deja `qrs/` escribible y `config.php` en modo 600.

---

## Notas sobre usuario y permisos

- Empujas como **root**, pero el hook hace `chown -R exxa:exxa` de los archivos
  del proyecto tras cada checkout, así que quedan con el propietario correcto.
- Los permisos se normalizan a `755` (carpetas) y `644` (archivos); `qrs/` a
  `755` y `config.php` a `600`.
- Si tu SSH permite entrar como `exxa`, puedes empujar como ese usuario y el
  `chown` sería innecesario (el hook lo ejecuta igualmente sin problema).
