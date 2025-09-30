CREATE DATABASE IF NOT EXISTS silabus_db;
USE silabus_db;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100),
  email VARCHAR(100) UNIQUE,
  password VARCHAR(255),
  rol ENUM('docente','coordinador','jefe') DEFAULT 'docente'
);

CREATE TABLE IF NOT EXISTS silabos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  carrera VARCHAR(150),
  anio INT,
  semestre VARCHAR(10), -- I o II
  unidad_didactica VARCHAR(200),
  archivo VARCHAR(255),
  docente_id INT,
  fecha_subida DATETIME,
  FOREIGN KEY (docente_id) REFERENCES usuarios(id)
);

CREATE TABLE IF NOT EXISTS unidades_didacticas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  programa VARCHAR(150) NOT NULL,
  semestre VARCHAR(10) NOT NULL, -- I..VI
  nombre VARCHAR(200) NOT NULL
);

-- SEED: Diseño y Programación Web (6 semestres)
INSERT INTO unidades_didacticas (programa, semestre, nombre) VALUES
-- Semestre I
('Diseño y Programación Web','I','Fundamentos de programación'),
('Diseño y Programación Web','I','Redes e internet'),
('Diseño y Programación Web','I','Análisis y diseño de sistemas'),
('Diseño y Programación Web','I','Introducción de base de datos'),
('Diseño y Programación Web','I','Arquitectura de computadoras'),
('Diseño y Programación Web','I','Comunicación oral'),
('Diseño y Programación Web','I','Aplicaciones en internet'),
-- Semestre II
('Diseño y Programación Web','II','Ofimática'),
('Diseño y Programación Web','II','Interpretación y producción textos'),
('Diseño y Programación Web','II','Metodología de desarrollo de software'),
('Diseño y Programación Web','II','Programación orientada a objetos'),
('Diseño y Programación Web','II','Arquitectura de servidores web'),
('Diseño y Programación Web','II','Aplicaciones sistematizadas'),
('Diseño y Programación Web','II','Taller de base de datos'),
-- Semestre III
('Diseño y Programación Web','III','Administración de base de datos'),
('Diseño y Programación Web','III','Programación de aplicaciones web'),
('Diseño y Programación Web','III','Diseño de interfaces web'),
('Diseño y Programación Web','III','Pruebas de software'),
('Diseño y Programación Web','III','Inglés para la comunicación oral'),
-- Semestre IV
('Diseño y Programación Web','IV','Desarrollo de entornos web'),
('Diseño y Programación Web','IV','Programación de soluciones web'),
('Diseño y Programación Web','IV','Proyectos de software'),
('Diseño y Programación Web','IV','Seguridad en aplicaciones web'),
('Diseño y Programación Web','IV','Comprensión y redacción en inglés'),
('Diseño y Programación Web','IV','Comportamiento ético'),
-- Semestre V
('Diseño y Programación Web','V','Programación de aplicaciones móviles'),
('Diseño y Programación Web','V','Marketing digital'),
('Diseño y Programación Web','V','Diseño de soluciones web'),
('Diseño y Programación Web','V','Gestión y administración de sitios web'),
('Diseño y Programación Web','V','Diagramación digital'),
('Diseño y Programación Web','V','Solución de problemas'),
('Diseño y Programación Web','V','Oportunidades de negocios'),
-- Semestre VI
('Diseño y Programación Web','VI','Plataforma de servicios web'),
('Diseño y Programación Web','VI','Ilustración y gráfica digital'),
('Diseño y Programación Web','VI','Administración de servidores web'),
('Diseño y Programación Web','VI','Comercio electrónico'),
('Diseño y Programación Web','VI','Plan de negocios');
