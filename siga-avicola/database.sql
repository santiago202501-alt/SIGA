-- ============================================================
--  SIGA – Sistema Inteligente de Gestión Avícola
--  Base de Datos MySQL para XAMPP / PHPMyAdmin
--  Ejecutar en PHPMyAdmin o con: mysql -u root -p < database.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS siga_avicola
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_spanish_ci;

USE siga_avicola;

-- ── GALPONES ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS galpon (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  nombre      VARCHAR(100)   NOT NULL,
  ubicacion   VARCHAR(150),
  capacidad   INT            NOT NULL DEFAULT 0,
  descripcion TEXT,
  estado      ENUM('activo','inactivo') DEFAULT 'activo',
  created_at  DATETIME       DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── LOTES ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS lote (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  id_galpon        INT            NOT NULL,
  codigo           VARCHAR(20)    NOT NULL UNIQUE,
  fecha_ingreso    DATE           NOT NULL,
  cantidad_inicial INT            NOT NULL,
  cantidad_actual  INT            NOT NULL,
  estado           ENUM('activo','cerrado','vendido') DEFAULT 'activo',
  observaciones    TEXT,
  created_at       DATETIME       DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (id_galpon) REFERENCES galpon(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ── TIPO DE SENSOR ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS tipo_sensor (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  nombre       VARCHAR(80)   NOT NULL,
  unidad       VARCHAR(20),
  umbral_warn  DECIMAL(10,2),
  umbral_crit  DECIMAL(10,2),
  icono        VARCHAR(10)   DEFAULT '📡'
) ENGINE=InnoDB;

-- ── SENSORES ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sensor (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  id_galpon  INT            NOT NULL,
  id_tipo    INT            NOT NULL,
  modelo     VARCHAR(100),
  estado     ENUM('activo','inactivo','falla') DEFAULT 'activo',
  created_at DATETIME       DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_galpon) REFERENCES galpon(id) ON DELETE RESTRICT,
  FOREIGN KEY (id_tipo)   REFERENCES tipo_sensor(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ── LECTURAS DE SENSOR ────────────────────────────────────
CREATE TABLE IF NOT EXISTS lectura_sensor (
  id        BIGINT AUTO_INCREMENT PRIMARY KEY,
  id_sensor INT            NOT NULL,
  valor     DECIMAL(10,4)  NOT NULL,
  fecha     DATETIME       DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_sensor) REFERENCES sensor(id) ON DELETE CASCADE,
  INDEX idx_sensor_fecha (id_sensor, fecha DESC)
) ENGINE=InnoDB;

-- ── CONSUMOS ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS consumo (
  id        BIGINT AUTO_INCREMENT PRIMARY KEY,
  id_lote   INT            NOT NULL,
  tipo      ENUM('alimento','agua') NOT NULL,
  cantidad  DECIMAL(10,2)  NOT NULL,
  fecha     DATE           NOT NULL DEFAULT (CURRENT_DATE),
  FOREIGN KEY (id_lote) REFERENCES lote(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ── ALERTAS ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS alerta (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  id_galpon   INT,
  id_sensor   INT,
  tipo        ENUM('crit','warn','ok') NOT NULL,
  titulo      VARCHAR(200) NOT NULL,
  descripcion TEXT,
  leida       TINYINT(1)  DEFAULT 0,
  created_at  DATETIME    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_galpon) REFERENCES galpon(id) ON DELETE SET NULL,
  FOREIGN KEY (id_sensor) REFERENCES sensor(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
--  DATOS INICIALES DE EJEMPLO
-- ============================================================

INSERT INTO galpon (nombre, ubicacion, capacidad, descripcion, estado) VALUES
  ('Galpón Norte', 'Sector A', 8000,  'Galpón principal zona norte', 'activo'),
  ('Galpón Sur',   'Sector B', 12000, 'Galpón de mayor capacidad',   'activo'),
  ('Galpón Este',  'Sector C', 6000,  'Galpón zona este reciente',   'activo');

INSERT INTO tipo_sensor (nombre, unidad, umbral_warn, umbral_crit, icono) VALUES
  ('Temperatura', '°C',  30,   35,   '🌡️'),
  ('Amoníaco',    'ppm', 25,   35,   '💨'),
  ('Humedad',     '%',   75,   85,   '💧'),
  ('CO₂',         'ppm', 1000, 1500, '🟤'),
  ('Luz',         'lux', NULL, NULL, '💡');

INSERT INTO sensor (id_galpon, id_tipo, modelo, estado) VALUES
  (1, 1, 'DHT22',   'activo'),
  (1, 2, 'MQ-135',  'activo'),
  (1, 3, 'DHT22',   'activo'),
  (2, 1, 'DHT22',   'activo'),
  (2, 2, 'MQ-135',  'activo'),
  (2, 4, 'MG-811',  'activo'),
  (3, 1, 'DHT22',   'activo'),
  (3, 2, 'MQ-135',  'activo');

INSERT INTO lote (id_galpon, codigo, fecha_ingreso, cantidad_inicial, cantidad_actual, estado) VALUES
  (1, 'L001', '2025-12-01', 5000, 4870, 'activo'),
  (1, 'L002', '2025-11-10', 3000, 2950, 'activo'),
  (2, 'L003', '2025-12-10', 8000, 7980, 'activo'),
  (3, 'L004', '2025-10-01', 4000, 3010, 'cerrado');

-- Lecturas simuladas
INSERT INTO lectura_sensor (id_sensor, valor, fecha) VALUES
  (1, 28.0, NOW()), (1, 27.5, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
  (2, 18.0, NOW()), (2, 17.0, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
  (3, 62.0, NOW()),
  (4, 32.0, NOW()), (4, 31.5, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
  (5, 28.0, NOW()),
  (6, 950.0, NOW()),
  (7, 38.0, NOW()), (7, 37.0, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
  (8, 45.0, NOW());

-- Alertas iniciales
INSERT INTO alerta (id_galpon, id_sensor, tipo, titulo, descripcion, leida) VALUES
  (3, 7, 'crit', 'Temperatura crítica – Galpón Este',  'Temperatura llegó a 38°C. Revisar ventilación inmediatamente.', 0),
  (3, 8, 'crit', 'Amoníaco alto – Galpón Este',        'Nivel de NH₃ en 45 ppm. Límite seguro: 25 ppm.',               0),
  (2, 4, 'warn', 'Temperatura elevada – Galpón Sur',   'Temperatura en 32°C, cerca del límite recomendado.',            0),
  (2, 5, 'warn', 'Amoníaco en advertencia – G. Sur',   'NH₃ en 28 ppm. Monitorear de cerca.',                           0),
  (1, 1, 'ok',   'Galpón Norte – Todo en orden',       'Todos los parámetros dentro del rango normal.',                 1);

-- Consumos de ejemplo
INSERT INTO consumo (id_lote, tipo, cantidad, fecha) VALUES
  (1, 'alimento', 320, DATE_SUB(CURDATE(), INTERVAL 6 DAY)),
  (1, 'alimento', 410, DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
  (1, 'alimento', 380, DATE_SUB(CURDATE(), INTERVAL 4 DAY)),
  (1, 'alimento', 430, DATE_SUB(CURDATE(), INTERVAL 3 DAY)),
  (1, 'alimento', 395, DATE_SUB(CURDATE(), INTERVAL 2 DAY)),
  (1, 'alimento', 460, DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
  (1, 'alimento', 510, CURDATE()),
  (1, 'agua',     800, CURDATE()),
  (2, 'agua',     600, CURDATE()),
  (3, 'agua',     1000,CURDATE());
