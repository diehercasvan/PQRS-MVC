-- --------------------------------------------------------
-- Crear Base de Datos
-- --------------------------------------------------------
DROP DATABASE IF EXISTS `pqrs_system`;
CREATE DATABASE `pqrs_system` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `pqrs_system`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- 1. TABLAS DE CATÁLOGO Y SEGURIDAD
-- --------------------------------------------------------

CREATE TABLE `user_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `user_status` (`id`, `name`, `description`) VALUES
(1, 'active', 'Usuario activo'),
(2, 'inactive', 'Usuario inactivo'),
(3, 'suspended', 'Usuario suspendido');

CREATE TABLE `role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `role` (`id`, `name`, `description`) VALUES
(1, 'admin', 'Administrador del Sistema'),
(2, 'user', 'Usuario Regular'),
(3, 'manager', 'Gerente de Departamento');

CREATE TABLE `document_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `document_type` (`id`, `name`, `description`) VALUES
(1, 'CC', 'Cédula de ciudadanía'),
(2, 'TI', 'Tarjeta de identidad'),
(3, 'CE', 'Cédula de Extranjería');

-- --------------------------------------------------------
-- 2. TABLAS DE USUARIOS Y PERFILES
-- --------------------------------------------------------

CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status_id` int(11) NOT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `status_id` (`status_id`),
  CONSTRAINT `user_ibfk_1` FOREIGN KEY (`status_id`) REFERENCES `user_status` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Usuario Admin por defecto (Password: admin123)
INSERT INTO `user` (`id`, `username`, `email`, `password_hash`, `status_id`) VALUES
(1, 'admin', 'admin@pqrs.com', '$2y$10$e.wXq.f2Q.3Pz2Q.3Pz2Qe.wXq.f2Q.3Pz2Q.3Pz2Q.3Pz2Q', 1);

CREATE TABLE `profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `document_type_id` int(11) NOT NULL,
  `document_number` varchar(50) NOT NULL,
  `photo_url` varchar(255) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `document_number` (`document_number`),
  KEY `document_type_id` (`document_type_id`),
  CONSTRAINT `profile_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `profile_ibfk_2` FOREIGN KEY (`document_type_id`) REFERENCES `document_type` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `user_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `status_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_role` (`user_id`,`role_id`),
  KEY `status_id` (`status_id`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `user_role_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_role_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_role_ibfk_3` FOREIGN KEY (`status_id`) REFERENCES `user_status` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Asignar rol admin al usuario admin
INSERT INTO `user_role` (`user_id`, `role_id`, `status_id`) VALUES (1, 1, 1);

-- --------------------------------------------------------
-- 3. TABLAS DEL SISTEMA DE PERMISOS DINÁMICOS
-- --------------------------------------------------------

CREATE TABLE `module` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `route` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `route` (`route`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `module` (`id`, `name`, `route`, `icon`, `description`) VALUES
(1, 'Dashboard', '#dashboard', 'bi-speedometer2', 'Panel principal'),
(2, 'Usuarios', '#user', 'bi-people', 'Gestión de usuarios'),
(3, 'Roles', '#role', 'bi-people-fill', 'Gestión de roles'),
(4, 'Perfiles', '#profile', 'bi-person-bounding-box', 'Gestión de perfiles'),
(5, 'PQRS', '#pqrs', 'bi-envelope-paper', 'Gestión de PQRS');

CREATE TABLE `permission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `permission` (`id`, `name`, `description`) VALUES
(1, 'view', 'Ver el módulo'),
(2, 'create', 'Crear registros'),
(3, 'edit', 'Editar registros'),
(4, 'delete', 'Eliminar registros'),
(5, 'print', 'Imprimir'),
(6, 'download', 'Descargar/Exportar');

-- Tabla Pivote Dinámica (Reemplaza a module_role)
CREATE TABLE `module_role_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_permission_assignment` (`module_id`, `role_id`, `user_id`, `permission_id`),
  KEY `module_id` (`module_id`),
  KEY `role_id` (`role_id`),
  KEY `user_id` (`user_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `mru_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `module` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `mru_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `mru_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `mru_ibfk_4` FOREIGN KEY (`permission_id`) REFERENCES `permission` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dar todos los permisos al usuario Admin en todos los módulos
INSERT INTO `module_role_user` (`module_id`, `role_id`, `user_id`, `permission_id`) VALUES
(1, 1, 1, 1), (1, 1, 1, 5), 
(2, 1, 1, 1), (2, 1, 1, 2), (2, 1, 1, 3), (2, 1, 1, 4), (2, 1, 1, 6),
(3, 1, 1, 1), (3, 1, 1, 2), (3, 1, 1, 3), (3, 1, 1, 4),
(4, 1, 1, 1), (4, 1, 1, 2), (4, 1, 1, 3), (4, 1, 1, 4),
(5, 1, 1, 1), (5, 1, 1, 2), (5, 1, 1, 3), (5, 1, 1, 4), (5, 1, 1, 5), (5, 1, 1, 6);

-- --------------------------------------------------------
-- 4. TABLAS DEL MÓDULO PQRS
-- --------------------------------------------------------

CREATE TABLE `pqrs_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `pqrs_type` (`id`, `name`, `description`) VALUES
(1, 'Petición', 'Solicitud de información o acción'),
(2, 'Queja', 'Manifestación de inconformidad'),
(3, 'Reclamo', 'Exigencia de un derecho o corrección'),
(4, 'Sugerencia', 'Propuesta para mejorar el servicio');

CREATE TABLE `pqrs_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `pqrs_status` (`id`, `name`, `color`) VALUES
(1, 'Radicada', 'secondary'),
(2, 'En Proceso', 'warning'),
(3, 'Resuelta', 'success'),
(4, 'Cerrada', 'dark'),
(5, 'Rechazada', 'danger');

CREATE TABLE `pqrs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `radicado` varchar(30) NOT NULL,
  `user_id` int(11) NOT NULL,
  `pqrs_type_id` int(11) NOT NULL,
  `pqrs_status_id` int(11) NOT NULL DEFAULT 1,
  `assigned_to` int(11) DEFAULT NULL,
  `subject` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `radicado` (`radicado`),
  CONSTRAINT `pqrs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `pqrs_ibfk_2` FOREIGN KEY (`pqrs_type_id`) REFERENCES `pqrs_type` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `pqrs_ibfk_3` FOREIGN KEY (`pqrs_status_id`) REFERENCES `pqrs_status` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `pqrs_ibfk_4` FOREIGN KEY (`assigned_to`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `pqrs_response` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pqrs_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  CONSTRAINT `pqrs_response_ibfk_1` FOREIGN KEY (`pqrs_id`) REFERENCES `pqrs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `pqrs_response_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;