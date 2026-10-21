
CREATE DATABASE IF NOT EXISTS agenda_contactos;

USE agenda_contactos;

CREATE TABLE usuarios (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  nombre      VARCHAR(100) NOT NULL,
  email       VARCHAR(150) NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL,
  api_key     VARCHAR(64)  NOT NULL UNIQUE,
  creado_en   DATETIME NOT NULL
);

CREATE TABLE contactos (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id      INT NOT NULL,
  nombre          VARCHAR(100) NOT NULL,
  apellido        VARCHAR(100) NOT NULL,
  email           VARCHAR(150) NOT NULL,
  empresa         VARCHAR(100) NOT NULL,
  fecha_nacimiento DATE NOT NULL,
  notas           TEXT NOT NULL,
  creado_en       DATETIME NOT NULL,
  actualizado_en  DATETIME NOT NULL,
  CONSTRAINT fk_contactos_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE telefonos (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  contacto_id  INT NOT NULL,
  numero       VARCHAR(20) NOT NULL,
  tipo         VARCHAR(20) NOT NULL,
  CONSTRAINT fk_telefonos_contacto
    FOREIGN KEY (contacto_id) REFERENCES contactos(id)
    ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE direcciones (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  contacto_id   INT NOT NULL,
  direccion     VARCHAR(200) NOT NULL,
  ciudad        VARCHAR(100) NOT NULL,
  departamento  VARCHAR(100) NOT NULL,
  pais          VARCHAR(100) NOT NULL,
  tipo          VARCHAR(20) NOT NULL,
  CONSTRAINT fk_direcciones_contacto
    FOREIGN KEY (contacto_id) REFERENCES contactos(id)
    ON DELETE CASCADE ON UPDATE CASCADE
);

INSERT INTO usuarios (nombre, email, password, api_key, creado_en) VALUES
('Administrador', 'admin@agenda.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'a1b2c3d4e5f60718293a4b5c6d7e8f90a1b2c3d4e5f60718293a4b5c6d7e8f90', NOW()),
('Jose Osorio', 'jose@agenda.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'f0e1d2c3b4a5968778695a4b3c2d1e0ff0e1d2c3b4a5968778695a4b3c2d1e0f', NOW());

INSERT INTO contactos (usuario_id, nombre, apellido, email, empresa, fecha_nacimiento, notas, creado_en, actualizado_en) VALUES
(1, 'Maria',   'Lopez',     'maria.lopez@gmail.com',    'Banco Industrial', '1990-04-12', 'Compañera de la universidad', NOW(), NOW()),
(1, 'Carlos',  'Hernandez', 'carlos.h@outlook.com',     'Tigo Guatemala',   '1985-09-23', 'Asesor de planes moviles',    NOW(), NOW()),
(1, 'Ana',     'Garcia',    'ana.garcia@yahoo.com',     'Farmacias Galeno', '1998-01-30', 'Vecina',                      NOW(), NOW()),
(1, 'Luis',    'Morales',   'lmorales@empresa.com.gt',  'Cementos Progreso','1979-11-05', 'Proveedor',                   NOW(), NOW()),
(2, 'Sofia',   'Ramirez',   'sofia.ramirez@gmail.com',  'Claro Guatemala',  '1995-06-18', 'Ejecutiva de ventas',         NOW(), NOW()),
(2, 'Diego',   'Castillo',  'diego.castillo@gmail.com', 'Universidad Mariano Galvez', '2000-03-02', 'Primo',             NOW(), NOW()),
(2, 'Valeria', 'Mendez',    'vmendez@hotmail.com',      'Pollo Campero',    '1992-12-14', 'Contacto de trabajo',         NOW(), NOW());

INSERT INTO telefonos (contacto_id, numero, tipo) VALUES
(1, '5512-3456', 'movil'),
(1, '2334-5566', 'trabajo'),
(2, '4021-7788', 'movil'),
(3, '3099-1122', 'movil'),
(3, '2440-1020', 'casa'),
(4, '2421-3000', 'trabajo'),
(5, '5788-9900', 'movil'),
(6, '4455-6677', 'movil'),
(7, '5123-4567', 'movil'),
(7, '2385-6000', 'trabajo');

INSERT INTO direcciones (contacto_id, direccion, ciudad, departamento, pais, tipo) VALUES
(1, '6a Avenida 10-25, Zona 1',          'Guatemala',          'Guatemala',      'Guatemala', 'casa'),
(1, '7a Avenida 5-10, Zona 4',           'Guatemala',          'Guatemala',      'Guatemala', 'trabajo'),
(2, 'Calzada Roosevelt 22-43, Zona 11',  'Guatemala',          'Guatemala',      'Guatemala', 'trabajo'),
(3, '4a Calle 3-15, Zona 2',             'Mixco',              'Guatemala',      'Guatemala', 'casa'),
(4, 'Km 17.5 Carretera a El Salvador',   'Santa Catarina Pinula','Guatemala',    'Guatemala', 'trabajo'),
(5, '15 Avenida 8-40, Zona 3',           'Quetzaltenango',     'Quetzaltenango', 'Guatemala', 'casa'),
(6, '2a Calle Poniente 12, Barrio San Sebastian', 'Antigua Guatemala', 'Sacatepequez', 'Guatemala', 'casa'),
(7, 'Boulevard Los Proceres 24-69, Zona 10', 'Guatemala',      'Guatemala',      'Guatemala', 'trabajo');
