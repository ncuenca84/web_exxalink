-- Registro de intentos de acceso por IP (anti fuerza bruta) — Exxalink S.A.S.
-- Crea únicamente esta tabla; no toca `certificados` ni `usuarios`.

CREATE TABLE IF NOT EXISTS intentos_login (
    ip       VARCHAR(45) NOT NULL PRIMARY KEY,  -- IPv4 / IPv6
    intentos INT         NOT NULL DEFAULT 0,
    ultimo   INT         NOT NULL DEFAULT 0     -- timestamp Unix del último intento
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
