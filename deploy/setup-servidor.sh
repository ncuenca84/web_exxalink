#!/bin/bash
#
# Configuración inicial del despliegue por Git — Exxalink S.A.S.
# Ejecutar UNA sola vez en el servidor (como root).
# Crea el repositorio bare e instala el hook post-receive.
#
set -euo pipefail

REPO="/home/exxa/repos/web_exxalink.git"
HOOK_SRC="$(cd "$(dirname "$0")" && pwd)/post-receive"

echo "==> Creando repositorio bare en $REPO"
mkdir -p /home/exxa/repos
if [ ! -d "$REPO" ]; then
    git init --bare "$REPO"
fi

echo "==> Instalando hook post-receive"
if [ ! -f "$HOOK_SRC" ]; then
    echo "ERROR: no se encontró $HOOK_SRC (ejecútalo desde la carpeta deploy/)." >&2
    exit 1
fi
cp "$HOOK_SRC" "$REPO/hooks/post-receive"
chmod +x "$REPO/hooks/post-receive"

echo "==> Ajustando propietario del repo"
chown -R exxa:exxa /home/exxa/repos

echo
echo "Listo. Ahora, desde tu equipo local:"
echo "  git remote add produccion ssh://root@TU_SERVIDOR:PUERTO$REPO"
echo "  git push produccion main"
