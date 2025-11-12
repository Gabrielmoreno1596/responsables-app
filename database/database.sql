-- responsables-app database schema
CREATE DATABASE IF NOT EXISTS cecnsr_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cecnsr_prod;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'admin',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS responsables (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  dui VARCHAR(20) NULL,
  telefono VARCHAR(30) NULL,
  correo VARCHAR(120) NULL,
  direccion VARCHAR(255) NULL,
  municipio VARCHAR(120) NULL,
  departamento VARCHAR(120) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_responsables_dui (dui)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS estudiantes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  grado VARCHAR(50) NULL,
  responsable_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_estudiantes_grado (grado),
  KEY idx_estudiantes_created (created_at),
  CONSTRAINT fk_estudiante_responsable FOREIGN KEY (responsable_id)
    REFERENCES responsables(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Default admin (user: admin / pass: admin123) - bcrypt hash
INSERT INTO users (username, password_hash, role)
VALUES ('admin', '$2b$12$UCgtbbrw7Xt1wLAF0qcsl.JTdXR7pjRM./0JxMrXTYOJGJngxcsvm', 'admin')
ON DUPLICATE KEY UPDATE username = VALUES(username);
