-- Esquema del sistema de certificados — Exxalink S.A.S.
-- Referencia del esquema completo de la base `exxa_certificados`.
--
-- NOTA: la base y la tabla `certificados` YA EXISTEN en producción con datos.
-- Este archivo es idempotente (CREATE TABLE IF NOT EXISTS, sin DROP ni ALTER):
-- ejecutarlo NO altera ni borra la tabla `certificados` existente.
-- Para una instalación ya en marcha basta con crear la tabla `usuarios`
-- (ver db/usuarios.sql), que es lo único nuevo.

-- Tabla de certificados (solo se crea si no existiera; no toca la actual).
CREATE TABLE IF NOT EXISTS certificados (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    codigo        VARCHAR(50)  NOT NULL UNIQUE,
    nombre        VARCHAR(100) NOT NULL,
    curso         VARCHAR(150) NOT NULL,
    fecha_emision DATE         NOT NULL,
    creado_en     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Usuarios del panel administrativo.
-- Las contraseñas se guardan SIEMPRE como hash (password_hash / PASSWORD_DEFAULT).
-- No insertes contraseñas en texto plano: usa tools/crear_admin.php.
CREATE TABLE IF NOT EXISTS usuarios (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    usuario    VARCHAR(50)  NOT NULL UNIQUE,
    clave_hash VARCHAR(255) NOT NULL,
    creado_en  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
