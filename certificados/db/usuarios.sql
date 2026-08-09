-- Tabla NUEVA para el login del panel — Exxalink S.A.S.
--
-- IMPORTANTE: esto NO toca la tabla `certificados` ni sus datos existentes.
-- Solo crea la tabla `usuarios` (si no existe) para almacenar las credenciales
-- del administrador cifradas con password_hash(). Es lo único que la base de
-- datos necesita para la versión modernizada.

CREATE TABLE IF NOT EXISTS usuarios (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    usuario    VARCHAR(50)  NOT NULL UNIQUE,
    clave_hash VARCHAR(255) NOT NULL,
    creado_en  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
