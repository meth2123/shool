-- Script pour créer la base de données gestion
CREATE DATABASE IF NOT EXISTS `gestion` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `gestion`;

-- Table users (table de base pour l'authentification)
CREATE TABLE IF NOT EXISTS `users` (
  `userid` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `usertype` enum('admin','teacher','student','parent','staff') NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table admin
CREATE TABLE IF NOT EXISTS `admin` (
  `id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table teachers
CREATE TABLE IF NOT EXISTS `teachers` (
  `id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_by` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `teachers_admin_fk` FOREIGN KEY (`created_by`) REFERENCES `admin` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table students
CREATE TABLE IF NOT EXISTS `students` (
  `id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_by` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `students_admin_fk` FOREIGN KEY (`created_by`) REFERENCES `admin` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table parents
CREATE TABLE IF NOT EXISTS `parents` (
  `id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_by` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `parents_admin_fk` FOREIGN KEY (`created_by`) REFERENCES `admin` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table staff
CREATE TABLE IF NOT EXISTS `staff` (
  `id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_by` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `staff_admin_fk` FOREIGN KEY (`created_by`) REFERENCES `admin` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table subscriptions
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_email` varchar(100) NOT NULL,
  `payment_status` enum('active','pending','failed','expired') NOT NULL DEFAULT 'pending',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `admin_email` (`admin_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insérer un administrateur de test
INSERT INTO `users` (`userid`, `password`, `usertype`, `status`) 
VALUES ('admin', '$2y$10$rBIbRTAXWWxRxDYkxqmesOYpL.VWyUYA7CRnGZqXcCdFdUn7OcKJa', 'admin', 'active');

INSERT INTO `admin` (`id`, `name`, `email`) 
VALUES ('admin', 'Administrateur', 'admin@example.com');

-- Insérer un abonnement actif pour l'administrateur
INSERT INTO `subscriptions` (`admin_email`, `payment_status`, `start_date`, `end_date`) 
VALUES ('admin@example.com', 'active', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR));

-- Table class (classes)
CREATE TABLE IF NOT EXISTS `class` (
  `id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `section` varchar(50) DEFAULT NULL,
  `created_by` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `class_admin_fk` FOREIGN KEY (`created_by`) REFERENCES `admin` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table course (cours)
CREATE TABLE IF NOT EXISTS `course` (
  `id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `teacherid` varchar(50) DEFAULT NULL,
  `classid` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `teacherid` (`teacherid`),
  KEY `classid` (`classid`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `course_teacher_fk` FOREIGN KEY (`teacherid`) REFERENCES `teachers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `course_class_fk` FOREIGN KEY (`classid`) REFERENCES `class` (`id`) ON DELETE SET NULL,
  CONSTRAINT `course_admin_fk` FOREIGN KEY (`created_by`) REFERENCES `admin` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insérer quelques données d'exemple
INSERT INTO `class` (`id`, `name`, `section`, `created_by`) 
VALUES ('CL001', 'Terminale S', 'Sciences', 'admin');

INSERT INTO `teachers` (`id`, `name`, `email`, `created_by`) 
VALUES ('T001', 'Prof Exemple', 'prof@example.com', 'admin');

INSERT INTO `course` (`id`, `name`, `teacherid`, `classid`, `description`, `created_by`) 
VALUES ('CO001', 'Mathématiques', 'T001', 'CL001', 'Cours de mathématiques pour Terminale S', 'admin');
