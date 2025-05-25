-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : sam. 24 mai 2025 à 16:13
-- Version du serveur : 8.2.0
-- Version de PHP : 8.2.13

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `gestion`
--

-- --------------------------------------------------------

--
-- Structure de la table `admin`
--

DROP TABLE IF EXISTS `admin`;
CREATE TABLE IF NOT EXISTS `admin` (
  `id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(13) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dob` date NOT NULL,
  `hiredate` date NOT NULL,
  `address` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sex` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `admin`
--

INSERT INTO `admin` (`id`, `name`, `password`, `phone`, `email`, `dob`, `hiredate`, `address`, `sex`) VALUES
('21', 'Admin Système', 'admin123', '778072570', 'methndiaye43@gmail.c', '0000-00-00', '0000-00-00', '', ''),
('ad-123-0', 'Prosen', '123', '01822804168', 'prosen@example.com', '1993-11-20', '2016-01-01', 'Dhaka,Cantonment', 'male'),
('ad-123-1', 'Rifat', '123', '01922000000', 'Rifat@gmail.com', '1992-05-12', '2016-04-24', 'Dhaka', 'Male'),
('ad-123-2', 'Rizvi', '123', '01922000012', 'rizvi@gmail.com', '1992-05-12', '2016-04-24', 'Dhaka', 'Male'),
('ad-123-3', 'Barid', '123', '01922012000', 'barid@gmail.com', '1992-05-12', '2016-04-24', 'Dhaka', 'Male'),
('MET2813', 'ndiaye', 'Alamine', '+221778072570', 'dmbosse104@gmail.com', '2025-05-24', '2025-05-24', 'meth ndiaye', 'male'),
('MET7586', 'ndiaye', '$2y$10$jdkr0D1D9RjdP', '+221778072570', 'methndiaye43@gmail.com', '2025-05-24', '2025-05-24', 'meth ndiaye', 'male');

-- --------------------------------------------------------

--
-- Structure de la table `admin_actions`
--

DROP TABLE IF EXISTS `admin_actions`;
CREATE TABLE IF NOT EXISTS `admin_actions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admin_id` varchar(20) NOT NULL,
  `action_type` enum('CREATE','UPDATE','DELETE') NOT NULL,
  `affected_table` varchar(50) NOT NULL,
  `affected_id` varchar(20) NOT NULL,
  `action_details` text,
  `action_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `attendedid` varchar(20) NOT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `attendance`
--

INSERT INTO `attendance` (`id`, `date`, `attendedid`, `created_by`) VALUES
(18, '2016-05-04', 'te-123-1', NULL),
(20, '2016-05-01', 'te-123-1', NULL),
(21, '2016-04-12', 'te-123-1', NULL),
(22, '2016-05-04', 'te-124-1', NULL),
(23, '2016-04-19', 'te-124-1', NULL),
(24, '2016-05-02', 'te-124-1', NULL),
(25, '2016-05-04', 'sta-123-1', NULL),
(26, '2016-05-05', 'sta-123-1', NULL),
(27, '2016-04-04', 'sta-123-1', NULL),
(28, '2016-04-05', 'sta-123-1', NULL),
(29, '2025-05-18', 'TE-MET-1596', 'ad-123-1'),
(30, '2025-05-18', 'STF001ITA', 'ad-123-1'),
(31, '2025-05-18', 'TE-MET-6288', 'ad-123-1'),
(37, '2025-05-22', 'TE-MET-1874', 'ad-123-1'),
(38, '2025-05-24', 'st200', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `availablecourse`
--

DROP TABLE IF EXISTS `availablecourse`;
CREATE TABLE IF NOT EXISTS `availablecourse` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `classid` varchar(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `availablecourse`
--

INSERT INTO `availablecourse` (`id`, `name`, `classid`) VALUES
(1, 'Bangla 1st', '1'),
(2, 'Bangla 1st', '2'),
(3, 'Bangla 1st', '3'),
(4, 'Bangla 1st', '4'),
(5, 'Bangla 1st', '5'),
(6, 'Bangla 1st', '6'),
(7, 'Bangla 1st', '7'),
(8, 'Bangla 1st', '8'),
(9, 'Bangla 1st', '9'),
(10, 'Bangla 1st', '10'),
(11, 'Bangla 2nd', '1'),
(12, 'Bangla 2nd', '2'),
(13, 'Bangla 2nd', '3'),
(14, 'Bangla 2nd', '4'),
(15, 'Bangla 2nd', '5'),
(16, 'Bangla 2nd', '6'),
(17, 'Bangla 2nd', '7'),
(18, 'Bangla 2nd', '8'),
(19, 'Bangla 2nd', '9'),
(20, 'Bangla 2nd', '10'),
(21, 'English 1st', '1'),
(22, 'English 1st', '2'),
(23, 'English 1st', '3'),
(24, 'English 1st', '4'),
(25, 'English 1st', '5'),
(26, 'English 1st', '6'),
(27, 'English 1st', '7'),
(28, 'English 1st', '8'),
(29, 'English 1st', '9'),
(30, 'English 1st', '10'),
(31, 'English 2nd', '1'),
(32, 'English 2nd', '2'),
(33, 'English 2nd', '3'),
(34, 'English 2nd', '4'),
(35, 'English 2nd', '5'),
(36, 'English 2nd', '6'),
(37, 'English 2nd', '7'),
(38, 'English 2nd', '8'),
(39, 'English 2nd', '9'),
(40, 'English 2nd', '10');

-- --------------------------------------------------------

--
-- Structure de la table `bulletins`
--

DROP TABLE IF EXISTS `bulletins`;
CREATE TABLE IF NOT EXISTS `bulletins` (
  `id` varchar(50) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `class_id` varchar(20) DEFAULT NULL,
  `period` enum('1','2','3') NOT NULL,
  `school_year` varchar(9) NOT NULL,
  `average` decimal(4,2) DEFAULT NULL,
  `student_rank` int DEFAULT NULL,
  `comments` text,
  `status` enum('draft','published') DEFAULT 'draft',
  `created_by` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `published_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `bulletin_signatures`
--

DROP TABLE IF EXISTS `bulletin_signatures`;
CREATE TABLE IF NOT EXISTS `bulletin_signatures` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bulletin_id` varchar(50) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `class_id` varchar(20) NOT NULL,
  `semester` int NOT NULL,
  `signed_by` varchar(20) NOT NULL,
  `signature_type` enum('director','teacher') NOT NULL,
  `signature_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_signature` (`bulletin_id`,`signature_type`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `bulletin_signatures`
--

INSERT INTO `bulletin_signatures` (`id`, `bulletin_id`, `student_id`, `class_id`, `semester`, `signed_by`, `signature_type`, `signature_date`) VALUES
(1, 'BUL-st0112-CLS-MAT-A-855-1', 'st0112', 'CLS-MAT-A-855', 1, 'ad-123-1', 'director', '2025-05-21 16:35:57');

-- --------------------------------------------------------

--
-- Structure de la table `class`
--

DROP TABLE IF EXISTS `class`;
CREATE TABLE IF NOT EXISTS `class` (
  `id` varchar(20) NOT NULL,
  `name` varchar(20) NOT NULL,
  `room` varchar(20) NOT NULL,
  `section` varchar(10) NOT NULL,
  `created_by` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_class_admin` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `class`
--

INSERT INTO `class` (`id`, `name`, `room`, `section`, `created_by`) VALUES
('CLS-CI-A-218', 'CI', '21', 'A', 'ad-123-1'),
('CLS-CI-A-879', 'ci', '11', 'A', 'ad-123-2'),
('CLS-MAT-A-855', 'maternell', '10', 'A', 'ad-123-1');

-- --------------------------------------------------------

--
-- Structure de la table `class_payment_amount`
--

DROP TABLE IF EXISTS `class_payment_amount`;
CREATE TABLE IF NOT EXISTS `class_payment_amount` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class_id` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_class` (`class_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `class_payment_amount`
--

INSERT INTO `class_payment_amount` (`id`, `class_id`, `amount`, `created_at`, `updated_at`) VALUES
(1, 'CLS-CI-A-218', 10000.00, '2025-05-22 22:57:18', '2025-05-22 22:57:18'),
(2, 'CLS-MAT-A-855', 10000.00, '2025-05-22 22:57:41', '2025-05-22 22:57:41');

-- --------------------------------------------------------

--
-- Structure de la table `class_schedule`
--

DROP TABLE IF EXISTS `class_schedule`;
CREATE TABLE IF NOT EXISTS `class_schedule` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class_id` varchar(20) NOT NULL,
  `subject_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `slot_id` int NOT NULL,
  `room` varchar(50) NOT NULL,
  `created_by` int NOT NULL COMMENT 'ID de l''administrateur qui a créé l''emploi du temps',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_schedule` (`class_id`,`slot_id`),
  UNIQUE KEY `unique_teacher_slot` (`teacher_id`,`slot_id`),
  KEY `subject_id` (`subject_id`),
  KEY `slot_id` (`slot_id`),
  KEY `created_by` (`created_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `course`
--

DROP TABLE IF EXISTS `course`;
CREATE TABLE IF NOT EXISTS `course` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  `teacherid` varchar(20) NOT NULL,
  `studentid` varchar(20) NOT NULL,
  `classid` varchar(20) NOT NULL,
  `created_by` varchar(20) DEFAULT NULL,
  `coefficient` decimal(3,2) DEFAULT '1.00',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `course`
--

INSERT INTO `course` (`id`, `name`, `teacherid`, `studentid`, `classid`, `created_by`, `coefficient`, `updated_at`) VALUES
(1, 'Bangla 1st', 'te-124-1', '', '1A', 'ad-123-0', 1.00, '2025-05-21 14:08:34'),
(2, 'Bangla 1st', 'te-124-1', '', '1A', 'ad-123-0', 1.00, '2025-05-21 14:08:34'),
(4, 'anglais', 'TE-ABD-4064', '', 'CLS-CI-A-218', 'ad-123-1', 3.00, '2025-05-22 20:03:24'),
(5, 'francais', 'TE-MET-1874', '', 'CLS-CI-A-218', 'ad-123-1', 2.00, '2025-05-22 20:03:24'),
(8, 'math', 'TE-MET-1874', '', 'CLS-MAT-A-855', 'ad-123-1', 1.00, '2025-05-22 20:45:23');

-- --------------------------------------------------------

--
-- Structure de la table `exams`
--

DROP TABLE IF EXISTS `exams`;
CREATE TABLE IF NOT EXISTS `exams` (
  `id` int NOT NULL AUTO_INCREMENT,
  `course_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `type` enum('Contrôle','Examen','Devoir','Projet') NOT NULL,
  `coefficient` decimal(3,2) NOT NULL DEFAULT '1.00',
  `created_by` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_course_exams` (`course_id`),
  KEY `idx_exam_date` (`date`),
  KEY `idx_exam_type` (`type`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `examschedule`
--

DROP TABLE IF EXISTS `examschedule`;
CREATE TABLE IF NOT EXISTS `examschedule` (
  `id` varchar(20) NOT NULL,
  `examdate` date NOT NULL,
  `time` varchar(20) NOT NULL,
  `courseid` varchar(20) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text,
  `created_by` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `examschedule`
--

INSERT INTO `examschedule` (`id`, `examdate`, `time`, `courseid`, `title`, `description`, `created_by`) VALUES
('145', '2016-05-06', '2:00-4:00', '1', NULL, NULL, 'admin'),
('EXAM', '2025-12-19', '10:11', '5', NULL, NULL, 'ad-123-1'),
('EXAM211', '2025-05-19', '10:10', '4', NULL, NULL, 'ad-123-1'),
('', '2025-08-10', '10:20', '4', 'vous etes vires', 'zzd', ''),
('', '2025-08-12', '10:12', '4', 'meth', 'knbb', ''),
('', '2025-08-12', '10:10', '4', 'meth', '1', ''),
('ASS-682f5ac8c987f', '2025-11-11', '10:10', '4', 'meth', '1', 'TE-ABD-4064'),
('ASS-682f5df2f281c', '2025-11-11', '10:21', '4', 'meth', 'a', 'TE-ABD-4064');

-- --------------------------------------------------------

--
-- Structure de la table `exam_results`
--

DROP TABLE IF EXISTS `exam_results`;
CREATE TABLE IF NOT EXISTS `exam_results` (
  `id` int NOT NULL AUTO_INCREMENT,
  `exam_id` int NOT NULL,
  `student_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `score` decimal(4,2) DEFAULT NULL,
  `comments` text,
  `created_by` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_exam_student` (`exam_id`,`student_id`),
  KEY `idx_exam_results` (`exam_id`,`student_id`),
  KEY `idx_student_results` (`student_id`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Structure de la table `grade`
--

DROP TABLE IF EXISTS `grade`;
CREATE TABLE IF NOT EXISTS `grade` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `teacher_id` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `grade` varchar(5) NOT NULL,
  `status` enum('pending','validated','rejected') DEFAULT 'pending',
  `course_id` int DEFAULT NULL,
  `class_id` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `created_by` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `validated_by` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `validated_at` timestamp NULL DEFAULT NULL,
  `rejected_by` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` mediumtext,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `grade`
--

INSERT INTO `grade` (`id`, `student_id`, `teacher_id`, `grade`, `status`, `course_id`, `class_id`, `created_by`, `created_at`, `validated_by`, `validated_at`, `rejected_by`, `rejected_at`, `rejection_reason`) VALUES
(1, 'st-123-1', '', 'C', 'pending', 8, '', '', '2025-05-18 23:58:12', NULL, NULL, NULL, NULL, NULL),
(2, 'st-123-1', '', 'F', 'pending', 4, '', '', '2025-05-18 23:58:12', NULL, NULL, NULL, NULL, NULL),
(3, 'st-125-1', '', 'D+', 'pending', 1, '', '', '2025-05-18 23:58:12', NULL, NULL, NULL, NULL, NULL),
(4, 'st-123-1', '', 'D+', 'pending', 1, '', '', '2025-05-18 23:58:12', NULL, NULL, NULL, NULL, NULL),
(5, 'st-124-1', '', 'C+', 'pending', 1, '', '', '2025-05-18 23:58:12', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `grade_coefficients`
--

DROP TABLE IF EXISTS `grade_coefficients`;
CREATE TABLE IF NOT EXISTS `grade_coefficients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class_id` varchar(20) NOT NULL,
  `grade_type` enum('devoir','examen') NOT NULL,
  `grade_number` int DEFAULT NULL,
  `coefficient` decimal(3,1) NOT NULL DEFAULT '1.0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_grade_coefficient` (`class_id`,`grade_type`,`grade_number`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ID de l''utilisateur (admin, enseignant ou élève)',
  `user_type` enum('admin','teacher','student') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Type d''utilisateur',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Titre de la notification',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Contenu de la notification',
  `type` enum('info','success','warning','error') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info' COMMENT 'Type de notification',
  `link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Lien optionnel associé à la notification',
  `is_read` tinyint(1) DEFAULT '0' COMMENT 'Indique si la notification a été lue',
  `created_by` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_user_notifications` (`user_id`,`user_type`),
  KEY `idx_notification_status` (`is_read`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Table des notifications';

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `user_type`, `title`, `message`, `type`, `link`, `is_read`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'st1', 'student', 'interclasse', 'les interclasse commence demain', 'success', '', 0, 'ad-123-1', '2025-05-24 10:58:07', '2025-05-24 10:58:07'),
(2, 'st001', 'student', 'vous etes vires', 'VOUS ETES VIRES', 'error', '', 1, 'ad-123-1', '2025-05-24 11:03:59', '2025-05-24 11:18:28'),
(3, 'sta200', 'student', 'vous etes vires', 'VOUS ETES VIRES', 'error', '', 0, 'ad-123-1', '2025-05-24 11:03:59', '2025-05-24 11:03:59'),
(4, 'st1', 'student', 'vous etes vires', 'VOUS ETES VIRES', 'error', '', 0, 'ad-123-1', '2025-05-24 11:03:59', '2025-05-24 11:03:59'),
(5, 'st200', 'student', 'vous etes vires', 'VOUS ETES VIRES', 'error', '', 0, 'ad-123-1', '2025-05-24 11:03:59', '2025-05-24 11:03:59'),
(6, 'st0112', 'student', 'vous etes vires', 'VOUS ETES VIRES', 'error', '', 1, 'ad-123-1', '2025-05-24 11:03:59', '2025-05-24 11:19:56'),
(7, 'te-123-1', 'teacher', 'meth', 'BON', 'warning', '', 0, 'ad-123-1', '2025-05-24 11:04:28', '2025-05-24 11:04:28'),
(8, 'te-124-1', 'teacher', 'meth', 'BON', 'warning', '', 0, 'ad-123-1', '2025-05-24 11:04:28', '2025-05-24 11:04:28'),
(9, 'te-125-1', 'teacher', 'meth', 'BON', 'warning', '', 0, 'ad-123-1', '2025-05-24 11:04:28', '2025-05-24 11:04:28'),
(10, 'te-126-1', 'teacher', 'meth', 'BON', 'warning', '', 0, 'ad-123-1', '2025-05-24 11:04:28', '2025-05-24 11:04:28'),
(11, 'te-127-1', 'teacher', 'meth', 'BON', 'warning', '', 0, 'ad-123-1', '2025-05-24 11:04:28', '2025-05-24 11:04:28'),
(12, 'TE-ABD-4064', 'teacher', 'meth', 'BON', 'warning', '', 1, 'ad-123-1', '2025-05-24 11:04:28', '2025-05-24 11:18:07'),
(13, 'TE-MET-1874', 'teacher', 'meth', 'BON', 'warning', '', 0, 'ad-123-1', '2025-05-24 11:04:28', '2025-05-24 11:04:28'),
(14, 'methndiaye43@gmail.com', 'admin', 'Confirmation de paiement', 'Le paiement de l\'abonnement a été confirmé. Votre accès est maintenant actif jusqu\'au 23/06/2025', 'success', NULL, 0, 'ad-123-1', '2025-05-24 11:55:50', '2025-05-24 11:55:50'),
(15, 'methndiaye43@gmail.com', 'admin', 'Confirmation de paiement', 'Le paiement de l\'abonnement a été confirmé. Votre accès est maintenant actif jusqu\'au 23/06/2025', 'success', NULL, 0, 'ad-123-1', '2025-05-24 11:57:22', '2025-05-24 11:57:22'),
(16, 'methndiaye43@gmail.com', 'admin', 'Compte administrateur créé', 'Votre compte administrateur a été créé avec succès. Utilisez les identifiants fournis dans l\'email pour vous connecter.', 'success', NULL, 0, 'ad-123-1', '2025-05-24 12:01:30', '2025-05-24 12:01:30'),
(17, 'dmbosse104@gmail.com', 'admin', 'Compte administrateur créé', 'Votre compte administrateur a été créé avec succès. Utilisez les identifiants fournis dans l\'email pour vous connecter.', 'success', NULL, 0, 'ad-123-1', '2025-05-24 12:14:14', '2025-05-24 12:14:14');

-- --------------------------------------------------------

--
-- Structure de la table `parent`
--

DROP TABLE IF EXISTS `parent`;
CREATE TABLE IF NOT EXISTS `parent` (
  `id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text,
  `created_by` varchar(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `parents`
--

DROP TABLE IF EXISTS `parents`;
CREATE TABLE IF NOT EXISTS `parents` (
  `id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fathername` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mothername` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fatherphone` varchar(13) COLLATE utf8mb4_unicode_ci NOT NULL,
  `motherphone` varchar(13) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `parents`
--

INSERT INTO `parents` (`id`, `password`, `fathername`, `mothername`, `fatherphone`, `motherphone`, `address`, `created_by`) VALUES
('DIO-PA001', 'Alamine', 'ABDOU', 'DIOP', '+221778072570', '789788989', 'ndoutt', 'MET2813'),
('MET-PA001', 'Alamine', 'ABDOU', 'DIOP', '774323456', '776543456', 'domicile', 'MET2813'),
('PA-123', '123456', 'meth', 'ndiaye', '778072570', '779726813', 'ndoutt', 'ad-123-1'),
('pa-123-1', '123', 'Mr.A', 'mrs.A', '01711000000', '01711000000', 'unknown', 'ad-123-0'),
('pa-124-1', '123', 'Mukles khan', 'morzina akter', '01724242424', '01924242314', 'Dhaka', 'ad-123-0');

-- --------------------------------------------------------

--
-- Structure de la table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) NOT NULL,
  `reset_code` varchar(32) NOT NULL,
  `expiry` datetime NOT NULL,
  `used` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_reset_code` (`reset_code`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `reset_code`, `expiry`, `used`, `created_at`) VALUES
(1, 'st-125-1', 'a7b6a4f733adf81b554ffdb2bd75be9c', '2025-05-23 01:04:56', 0, '2025-05-23 00:04:56'),
(2, 'st-125-1', 'f3429074e47f05d6c067672b5b6aaad9', '2025-05-23 01:05:55', 0, '2025-05-23 00:05:55'),
(3, 'st-125-1', '816e82f4ec9b0feb3995a2b173aebeca', '2025-05-23 01:46:20', 0, '2025-05-23 00:46:20'),
(4, 'st001', '54d832904bc05b4ad118d71603e6c406', '2025-05-23 01:51:58', 0, '2025-05-23 00:51:58'),
(5, 'st001', 'a67b68c12ad6da3a130921a76060e324', '2025-05-23 01:57:36', 1, '2025-05-23 00:57:36'),
(6, 'st1', '92bf61e648231ce97de9af2e29729af5', '2025-05-24 03:25:22', 0, '2025-05-24 02:25:22'),
(7, 'MET2813', '51f0f7a42c7103e55f68975789854952', '2025-05-24 13:17:04', 1, '2025-05-24 12:17:04'),
(8, 'TE-MET-8005', '0fa375526edc473bcd57d875d40e31cd', '2025-05-24 15:17:08', 0, '2025-05-24 14:17:08'),
(9, 'TE-MET-8005', '40ebf9652ec9a3b003f09b926b9ab1ac', '2025-05-24 15:18:04', 0, '2025-05-24 14:18:04'),
(10, 'TE-MET-8005', '71b3b933286863bfc413a0570a352d51', '2025-05-24 15:41:57', 1, '2025-05-24 14:41:57');

-- --------------------------------------------------------

--
-- Structure de la table `payment`
--

DROP TABLE IF EXISTS `payment`;
CREATE TABLE IF NOT EXISTS `payment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `studentid` varchar(20) NOT NULL,
  `amount` double NOT NULL,
  `month` varchar(10) NOT NULL,
  `year` varchar(5) NOT NULL,
  `created_by` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `payment`
--

INSERT INTO `payment` (`id`, `studentid`, `amount`, `month`, `year`, `created_by`) VALUES
(1, 'st-123-1', 500, '5', '2016', 'ad-123-0'),
(2, 'st-123-1', 500, '4', '2016', 'ad-123-0'),
(3, 'st-124-1', 500, '5', '2016', 'ad-123-0'),
(4, 'st1', 10000, '5', '2025', ''),
(5, 'st1', 100000, '3', '2025', ''),
(6, 'st1', 1000, '5', '2025', ''),
(7, 'st1', 1000, '5', '2025', 'ad-123-1'),
(8, 'st1', 2000, '5', '2025', 'ad-123-1'),
(9, 'st0112', 2000, '5', '2025', 'ad-123-1');

-- --------------------------------------------------------

--
-- Structure de la table `report`
--

DROP TABLE IF EXISTS `report`;
CREATE TABLE IF NOT EXISTS `report` (
  `reportid` int NOT NULL AUTO_INCREMENT,
  `studentid` varchar(20) NOT NULL,
  `teacherid` varchar(20) NOT NULL,
  `message` varchar(500) NOT NULL,
  `courseid` varchar(20) NOT NULL,
  PRIMARY KEY (`reportid`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `report`
--

INSERT INTO `report` (`reportid`, `studentid`, `teacherid`, `message`, `courseid`) VALUES
(1, 'st-123-1', 'te-123-1', 'Good Boy', '790'),
(2, 'st-124-1', 'te-123-1', 'Good boy But not honest.', '790'),
(3, 'st-123-1', 'te-124-1', ' good', '1');

-- --------------------------------------------------------

--
-- Structure de la table `staff`
--

DROP TABLE IF EXISTS `staff`;
CREATE TABLE IF NOT EXISTS `staff` (
  `id` varchar(20) NOT NULL,
  `name` varchar(20) NOT NULL,
  `password` varchar(20) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `sex` varchar(7) NOT NULL,
  `dob` date NOT NULL,
  `hiredate` date NOT NULL,
  `address` varchar(30) NOT NULL,
  `salary` double NOT NULL,
  `created_by` varchar(20) DEFAULT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `staff`
--

INSERT INTO `staff` (`id`, `name`, `password`, `phone`, `email`, `sex`, `dob`, `hiredate`, `address`, `salary`, `created_by`) VALUES
('sta-123-1', 'Fogg', 'doule123sta-123-1', '01913827384', 'fog@example.com', 'male', '1985-12-18', '2016-01-01', 'dhaka', 900000, 'ad-123-0'),
('sta-124-1', 'Eyasin', 'doule123', '01913827384', 'fog@example.com', 'Male', '1998-03-25', '2016-05-03', 'dhaka', 60000, 'ad-123-0'),
('sta-125-1', 'Robin', 'doule123', '01913827384', 'fog@example.com', 'Male', '1992-12-12', '2016-05-03', 'dhaka', 10000, 'ad-123-0'),
('sta-126-1', 'Tanjil  Ahmed', 'doule123', '01913827384', 'fog@example.com', 'Male', '0000-00-00', '2016-05-05', 'dhaka', 600000, 'ad-123-0'),
('STF001ITA', 'meth ndiaye', 'doule123', '01913827384', 'fog@example.com', 'F', '0000-00-00', '2025-05-18', 'dhaka', 10000, 'ad-123-1');

-- --------------------------------------------------------

--
-- Structure de la table `staff_salary_history`
--

DROP TABLE IF EXISTS `staff_salary_history`;
CREATE TABLE IF NOT EXISTS `staff_salary_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `staff_id` varchar(20) NOT NULL,
  `month` int NOT NULL,
  `year` int NOT NULL,
  `base_salary` decimal(10,2) NOT NULL,
  `days_present` int NOT NULL,
  `days_absent` int NOT NULL,
  `final_salary` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `created_by` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `month_year_staff` (`month`,`year`,`staff_id`),
  KEY `staff_id` (`staff_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `staff_salary_history`
--

INSERT INTO `staff_salary_history` (`id`, `staff_id`, `month`, `year`, `base_salary`, `days_present`, `days_absent`, `final_salary`, `payment_date`, `created_by`) VALUES
(1, 'STF001ITA', 5, 2025, 10000.00, 1, 30, 323.00, '2025-05-18', '0');

-- --------------------------------------------------------

--
-- Structure de la table `students`
--

DROP TABLE IF EXISTS `students`;
CREATE TABLE IF NOT EXISTS `students` (
  `id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(13) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sex` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dob` date NOT NULL,
  `addmissiondate` date NOT NULL,
  `address` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parentid` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `classid` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `students`
--

INSERT INTO `students` (`id`, `name`, `password`, `phone`, `email`, `sex`, `dob`, `addmissiondate`, `address`, `parentid`, `classid`, `created_by`) VALUES
('st-123-1', 'mokbul', '123', '01681000000', 'mokbul@example.com', 'male', '2009-06-24', '2016-01-01', 'unknown', 'pa-123-1', '1A', 'ad-123-0'),
('st-124-1', 'rashid', '123', '018204679811', 'rashid@gmail.com', 'male', '1994-01-14', '2015-12-24', 'Dhaka', 'pa-123-1', '1A', 'ad-123-0'),
('st-125-1', 'Barid Hossain', '123', '01824242525', 'barid@gmail.com', 'Male', '1987-02-05', '2016-05-05', 'Dhaka', 'pa-124-1', '5A', 'ad-123-0'),
('st001', 'bellas', 'bonjour', '778072570', 'dmbosse104@gmail.com', 'Male', '2000-11-12', '2025-05-23', 'ndoutt', 'pa-123-1', 'CLS-CI-A-218', 'ad-123-1'),
('st0112', 'Mouhamed alamine mfg', '$2y$10$CDrSJTSYX7Keu5EU0m00U.CMHe9eS2ByUEyuRPHSfmVHIvWRBGl2y', '778072570', 'mouhamedalamine.ndia', 'Male', '4567-03-12', '2025-05-18', 'domicile', 'PA-123', 'CLS-MAT-A-855', 'ad-123-1'),
('st1', 'meth ndiaye', '$2y$10$uAm7SXDIsBh4dplQJ4DHa.s0YTLtu/lZ8xHyaAdG/hvWJpL8iYZai', '778072570', 'methndiaye43@gmail.com', 'Male', '2001-11-10', '2025-05-18', 'ndoutt', 'pa-123-1', '4B', 'ad-123-1'),
('st200', 'methdiaye', '$2y$10$x3pXRf3TEMwXtrm14mVL7uZKMFLFWykWx4Nvw9ODDkx9uRXQwgdei', '770237612', 'mouhamedalamine.ndia', 'Male', '2001-11-10', '2025-05-21', 'ndoutt', 'PA-123', 'CLS-CI-A-218', 'ad-123-1'),
('sta200', 'ko', '$2y$10$lwyDr9DtIW/2lW6mNIMBVOy5oWd5Y2kLKGmfWkJiKhgHawJ.QL6Re', '770237612', 'mouhamedalamine.ndia', 'Male', '2001-12-10', '2025-05-21', 'ndoutt', 'PA-123', 'CLS-MAT-A-855', 'ad-123-1');

-- --------------------------------------------------------

--
-- Structure de la table `student_teacher_course`
--

DROP TABLE IF EXISTS `student_teacher_course`;
CREATE TABLE IF NOT EXISTS `student_teacher_course` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `teacher_id` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `course_id` int NOT NULL,
  `class_id` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `created_by` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `grade` decimal(4,2) DEFAULT NULL,
  `semester` tinyint DEFAULT '1',
  `updated_at` timestamp NULL DEFAULT NULL,
  `grade_type` enum('devoir','examen') DEFAULT NULL,
  `grade_number` int DEFAULT '1',
  `coefficient` decimal(3,2) DEFAULT '1.00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_grade` (`student_id`,`teacher_id`,`course_id`,`class_id`,`grade_type`,`grade_number`,`semester`),
  KEY `teacher_id` (`teacher_id`),
  KEY `course_id` (`course_id`),
  KEY `class_id` (`class_id`),
  KEY `created_by` (`created_by`)
) ;

--
-- Déchargement des données de la table `student_teacher_course`
--

INSERT INTO `student_teacher_course` (`id`, `student_id`, `teacher_id`, `course_id`, `class_id`, `created_by`, `created_at`, `grade`, `semester`, `updated_at`, `grade_type`, `grade_number`, `coefficient`) VALUES
(3, 'st0112', 'TE-MET-1874', 4, 'CLS-MAT-A-855', 'ad-123-1', '2025-05-19 03:33:18', 10.00, 1, '2025-05-21 16:45:59', 'devoir', 1, 2.00),
(108, 'st0112', 'TE-MET-1874', 4, 'CLS-MAT-A-855', 'ad-123-1', '2025-05-21 16:07:53', 12.00, 1, '2025-05-21 16:45:59', 'devoir', 2, 2.00),
(114, 'st0112', 'TE-MET-1874', 4, 'CLS-MAT-A-855', 'ad-123-1', '2025-05-21 16:16:54', 19.00, 1, '2025-05-21 16:45:59', 'examen', 1, 3.00),
(115, 'st0112', 'TE-MET-1874', 4, 'CLS-MAT-A-855', 'ad-123-1', '2025-05-21 17:02:43', 10.00, 2, NULL, 'devoir', 1, 1.00),
(116, 'st0112', 'TE-MET-1874', 4, 'CLS-MAT-A-855', 'ad-123-1', '2025-05-21 17:02:43', 14.00, 2, NULL, 'devoir', 2, 1.00),
(117, 'st0112', 'TE-MET-1874', 4, 'CLS-MAT-A-855', 'ad-123-1', '2025-05-21 17:02:43', 10.00, 2, NULL, 'examen', 1, 3.00),
(118, 'st0112', 'TE-MET-1874', 4, 'CLS-MAT-A-855', 'ad-123-1', '2025-05-21 17:03:17', 10.00, 3, NULL, 'devoir', 1, 1.00),
(119, 'st0112', 'TE-MET-1874', 4, 'CLS-MAT-A-855', 'ad-123-1', '2025-05-21 17:03:17', 16.00, 3, NULL, 'devoir', 2, 1.00),
(120, 'st0112', 'TE-MET-1874', 4, 'CLS-MAT-A-855', 'ad-123-1', '2025-05-21 17:03:17', 9.00, 3, NULL, 'examen', 1, 3.00),
(122, 'st0112', 'TE-MET-1874', 8, 'CLS-MAT-A-855', 'ad-123-1', '2025-05-21 18:04:08', NULL, 1, NULL, NULL, 1, 1.00),
(133, 'st200', 'TE-ABD-4064', 4, 'CLS-CI-A-218', 'ad-123-1', '2025-05-22 11:35:59', NULL, 1, NULL, NULL, 1, 1.00),
(134, 'st200', 'TE-ABD-4064', 5, 'CLS-CI-A-218', 'ad-123-1', '2025-05-22 11:37:02', NULL, 1, NULL, NULL, 1, 1.00),
(137, 'st200', 'TE-ABD-4064', 4, 'CLS-CI-A-218', 'ad-123-1', '2025-05-22 19:08:00', 10.00, 1, '2025-05-22 19:08:00', 'examen', 1, 3.00),
(138, 'st200', 'TE-ABD-4064', 4, 'CLS-CI-A-218', 'ad-123-1', '2025-05-22 19:08:18', 16.00, 1, '2025-05-22 19:08:18', 'devoir', 1, 1.00),
(139, 'st200', 'TE-ABD-4064', 4, 'CLS-CI-A-218', 'ad-123-1', '2025-05-22 19:08:32', 19.00, 1, '2025-05-22 19:08:32', 'devoir', 2, 1.00),
(140, 'st200', 'TE-MET-1874', 5, 'CLS-CI-A-218', 'ad-123-1', '2025-05-22 19:21:04', NULL, 1, NULL, NULL, 1, 1.00),
(141, 'st200', 'TE-MET-1874', 5, 'CLS-CI-A-218', 'ad-123-1', '2025-05-22 19:43:54', 10.00, 1, '2025-05-22 19:43:54', 'devoir', 1, 1.00),
(142, 'st200', 'TE-MET-1874', 5, 'CLS-CI-A-218', 'ad-123-1', '2025-05-22 20:06:21', 5.50, 1, '2025-05-22 20:06:21', 'devoir', 2, 1.00),
(143, 'st200', 'TE-MET-1874', 5, 'CLS-CI-A-218', 'ad-123-1', '2025-05-22 20:08:58', 10.00, 1, '2025-05-22 20:08:58', 'examen', 1, 1.00);

-- --------------------------------------------------------

--
-- Structure de la table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
CREATE TABLE IF NOT EXISTS `subjects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `created_by` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `subjects`
--

INSERT INTO `subjects` (`id`, `name`, `description`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Mathématiques', 'Cours de mathématiques', 'ad-123-0', '2025-05-24 09:42:15', '2025-05-24 09:42:15'),
(2, 'Français', 'Cours de français', 'ad-123-0', '2025-05-24 09:42:15', '2025-05-24 09:42:15'),
(3, 'Anglais', 'Cours d\'anglais', 'ad-123-0', '2025-05-24 09:42:15', '2025-05-24 09:42:15'),
(4, 'Histoire', 'Cours d\'histoire', 'ad-123-0', '2025-05-24 09:42:15', '2025-05-24 09:42:15'),
(5, 'Géographie', 'Cours de géographie', 'ad-123-0', '2025-05-24 09:42:15', '2025-05-24 09:42:15'),
(6, 'Sciences', 'Cours de sciences', 'ad-123-0', '2025-05-24 09:42:15', '2025-05-24 09:42:15'),
(7, 'Informatique', 'Cours d\'informatique', 'ad-123-0', '2025-05-24 09:42:15', '2025-05-24 09:42:15'),
(8, 'Sport', 'Cours d\'éducation physique', 'ad-123-0', '2025-05-24 09:42:15', '2025-05-24 09:42:15');

-- --------------------------------------------------------

--
-- Structure de la table `subscriptions`
--

DROP TABLE IF EXISTS `subscriptions`;
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `school_name` varchar(255) NOT NULL,
  `director_name` varchar(100) NOT NULL,
  `admin_email` varchar(255) NOT NULL,
  `admin_phone` varchar(20) NOT NULL,
  `address` text,
  `subscription_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `expiry_date` datetime NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '15000.00',
  `payment_status` enum('pending','completed','failed','expired') DEFAULT 'pending',
  `payment_reference` varchar(100) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `subscriptions`
--

INSERT INTO `subscriptions` (`id`, `school_name`, `director_name`, `admin_email`, `admin_phone`, `address`, `subscription_date`, `expiry_date`, `amount`, `payment_status`, `payment_reference`, `payment_method`, `transaction_id`, `created_at`, `updated_at`) VALUES
(1, 'meth ndiaye', '', 'methndiaye43@gmail.com', '778072570', NULL, '2025-05-23 01:30:09', '2025-06-23 01:30:09', 15000.00, 'completed', 'MzdH6OD6RMBP4JBBTIrK', 'free_money', NULL, '2025-05-23 01:30:09', '2025-05-24 11:46:52'),
(2, 'meth ndiaye', '', 'methndiaye43@gmail.com', '778072570', NULL, '2025-05-23 12:42:51', '2025-06-23 12:42:51', 15000.00, 'completed', 'SUB-1748004171-8424', 'orange_money', NULL, '2025-05-23 12:42:51', '2025-05-24 11:57:22'),
(3, 'meth ndiaye', 'ndiaye', 'methndiaye43@gmail.com', '+221778072570', '', '2025-05-23 17:07:12', '0000-00-00 00:00:00', 15000.00, 'completed', NULL, NULL, NULL, '2025-05-23 17:07:12', '2025-05-24 12:01:30'),
(4, 'meth ndiaye', 'ndiaye', 'methndiaye43@gmail.com', '+221778072570', 'ndoutt', '2025-05-23 20:29:42', '0000-00-00 00:00:00', 15000.00, 'completed', NULL, NULL, NULL, '2025-05-23 20:29:42', '2025-05-24 12:06:55'),
(5, 'meth ndiaye', 'ndiaye', 'methndiaye43@gmail.com', '+221778072570', 'domicile', '2025-05-23 20:44:59', '0000-00-00 00:00:00', 15000.00, 'completed', NULL, NULL, NULL, '2025-05-23 20:44:59', '2025-05-24 15:34:43'),
(6, 'meth ndiaye', 'ndiaye', 'methndiaye43@gmail.com', '+221778072570', '', '2025-05-23 20:48:53', '0000-00-00 00:00:00', 15000.00, 'pending', NULL, NULL, NULL, '2025-05-23 20:48:53', '2025-05-23 20:48:53'),
(7, 'meth ndiaye', 'ndiaye', 'dmbosse104@gmail.com', '+221778072570', 'domicile', '2025-05-23 20:53:07', '0000-00-00 00:00:00', 15000.00, 'completed', NULL, NULL, NULL, '2025-05-23 20:53:07', '2025-05-24 15:36:26'),
(8, 'meth ndiaye', 'ndiaye', 'dmbosse104@gmail.com', '+221778072570', 'domicile', '2025-05-23 20:53:20', '0000-00-00 00:00:00', 15000.00, 'pending', NULL, NULL, NULL, '2025-05-23 20:53:20', '2025-05-24 15:01:11'),
(9, 'meth ndiaye', 'ndiaye', 'methndiaye43@gmail.com', '+221778072570', '', '2025-05-23 20:55:46', '0000-00-00 00:00:00', 15000.00, 'pending', NULL, NULL, NULL, '2025-05-23 20:55:46', '2025-05-23 20:55:46'),
(10, 'meth ndiaye', 'ndiaye', 'methndiaye43@gmail.com', '+221778072570', '', '2025-05-23 20:56:44', '0000-00-00 00:00:00', 15000.00, 'pending', NULL, NULL, NULL, '2025-05-23 20:56:44', '2025-05-23 20:56:44'),
(11, 'meth ndiaye', 'ndiaye', 'methndiaye43@gmail.com', '+221778072570', '', '2025-05-23 20:59:07', '0000-00-00 00:00:00', 15000.00, 'pending', NULL, NULL, NULL, '2025-05-23 20:59:07', '2025-05-23 20:59:07'),
(12, 'meth ndiaye', 'ndiaye', 'methndiaye43@gmail.com', '+221778072570', '', '2025-05-23 20:59:12', '0000-00-00 00:00:00', 15000.00, 'pending', NULL, NULL, NULL, '2025-05-23 20:59:12', '2025-05-23 20:59:12'),
(13, 'meth ndiaye', 'ndiaye', 'methndiaye43@gmail.com', '+221778072570', '', '2025-05-23 21:11:29', '0000-00-00 00:00:00', 15000.00, 'pending', NULL, NULL, NULL, '2025-05-23 21:11:29', '2025-05-23 21:11:29'),
(14, 'meth ndiaye', 'ndiaye', 'methndiaye43@gmail.com', '+221778072570', '', '2025-05-23 21:13:19', '0000-00-00 00:00:00', 15000.00, 'pending', NULL, NULL, NULL, '2025-05-23 21:13:19', '2025-05-23 21:13:19'),
(15, 'meth ndiaye', 'ndiaye', 'methndiaye43@gmail.com', '+221778072570', '', '2025-05-23 21:17:11', '0000-00-00 00:00:00', 15000.00, 'pending', NULL, NULL, NULL, '2025-05-23 21:17:11', '2025-05-23 21:17:11'),
(16, 'meth ndiaye', 'ndiaye', 'methndiaye43@gmail.com', '+221778072570', '', '2025-05-23 21:19:37', '0000-00-00 00:00:00', 15000.00, 'pending', NULL, NULL, NULL, '2025-05-23 21:19:37', '2025-05-23 21:19:37'),
(17, 'meth ndiaye', 'ndiaye', 'methndiaye43@gmail.com', '+221778072570', '', '2025-05-23 21:21:44', '0000-00-00 00:00:00', 15000.00, 'pending', 'VMTppQhiDfxKKnkslDvW', NULL, NULL, '2025-05-23 21:21:44', '2025-05-23 21:21:45'),
(18, 'meth ndiaye', 'ndiaye', 'methndiaye43@gmail.com', '+221778072570', '', '2025-05-23 21:22:16', '0000-00-00 00:00:00', 15000.00, 'pending', 'R5xsnfebvokDsLifDH4k', NULL, NULL, '2025-05-23 21:22:16', '2025-05-23 21:22:18'),
(19, 'meth ndiaye', 'ndiaye', 'methndiaye43@gmail.com', '+221778072570', 'ndoutt', '2025-05-23 21:23:49', '0000-00-00 00:00:00', 15000.00, 'pending', '2smmYamW6FOToYOMrga8', NULL, NULL, '2025-05-23 21:23:49', '2025-05-23 21:23:51'),
(20, 'meth ndiaye', 'ndiaye', 'methndiaye43@gmail.com', '+221778072570', '', '2025-05-24 08:31:47', '0000-00-00 00:00:00', 15000.00, 'pending', NULL, NULL, NULL, '2025-05-24 08:31:47', '2025-05-24 08:31:47'),
(21, 'meth ndiaye', 'ndiaye', 'methndiaye43@gmail.com', '+221778072570', '', '2025-05-24 08:33:02', '0000-00-00 00:00:00', 15000.00, 'pending', NULL, NULL, NULL, '2025-05-24 08:33:02', '2025-05-24 08:33:02'),
(22, 'meth ndiaye', 'ndiaye', 'methndiaye43@gmail.com', '+221778072570', '', '2025-05-24 08:34:16', '0000-00-00 00:00:00', 15000.00, 'pending', NULL, NULL, NULL, '2025-05-24 08:34:16', '2025-05-24 08:34:16'),
(23, 'meth ndiaye', 'ndiaye', 'methndiaye43@gmail.com', '+221778072570', '', '2025-05-24 08:34:28', '0000-00-00 00:00:00', 15000.00, 'pending', NULL, NULL, NULL, '2025-05-24 08:34:28', '2025-05-24 08:34:28'),
(24, 'meth ndiaye', 'ndiaye', 'methndiaye43@gmail.com', '+221778072570', '', '2025-05-24 08:35:43', '0000-00-00 00:00:00', 15000.00, 'pending', 'v21kclTY1mBw1fFPMpfs', NULL, NULL, '2025-05-24 08:35:43', '2025-05-24 08:35:45'),
(25, 'meth ndiaye', 'ndiaye', 'methndiaye43@gmail.com', '+221778072570', 'ndoutt', '2025-05-24 10:11:12', '0000-00-00 00:00:00', 15000.00, 'pending', NULL, NULL, NULL, '2025-05-24 10:11:12', '2025-05-24 10:11:12'),
(26, 'meth ndiaye', 'ndiaye', 'methndiaye43@gmail.com', '+221778072570', 'ndoutt', '2025-05-24 10:13:05', '0000-00-00 00:00:00', 15000.00, 'pending', 'fAoOXN3IRedmCXEVGPd3', NULL, NULL, '2025-05-24 10:13:05', '2025-05-24 10:13:08'),
(27, 'meth ndiaye', 'ndiaye', 'methndiaye43@gmail.com', '+221778072570', 'ndoutt', '2025-05-24 10:13:21', '0000-00-00 00:00:00', 15000.00, 'pending', '4LrBvoUiO3Q6p1yXMNWZ', NULL, NULL, '2025-05-24 10:13:21', '2025-05-24 10:13:24'),
(28, 'meth ndiaye', 'ndiaye', 'dmbosse104@gmail.com', '+221778072570', 'ndoutt', '2025-05-24 12:07:34', '0000-00-00 00:00:00', 15000.00, 'pending', 'Z1mrvr5l9NcGAlgbpspc', NULL, NULL, '2025-05-24 12:07:34', '2025-05-24 12:07:35'),
(29, 'meth ndiaye', 'ndiaye', 'dmbosse104@gmail.com', '+221778072570', '', '2025-05-24 12:08:53', '0000-00-00 00:00:00', 15000.00, 'pending', 'LP19no0fOXX3qPgdzP9L', NULL, NULL, '2025-05-24 12:08:53', '2025-05-24 12:08:55'),
(30, 'meth ndiaye', 'ndiaye', 'dmbosse104@gmail.com', '+221778072570', '', '2025-05-24 12:08:56', '0000-00-00 00:00:00', 15000.00, 'pending', 'mBAPsjuROzvE7SKlLjot', NULL, NULL, '2025-05-24 12:08:56', '2025-05-24 12:08:58'),
(31, 'meth ndiaye', 'ndiaye', 'dmbosse104@gmail.com', '+221778072570', '', '2025-05-24 12:08:57', '0000-00-00 00:00:00', 15000.00, 'pending', '7GCeI6wZ5QQASXVDtuKk', NULL, NULL, '2025-05-24 12:08:57', '2025-05-24 12:08:59'),
(32, 'meth ndiaye', 'ndiaye', 'dmbosse104@gmail.com', '+221778072570', 'ndoutt', '2025-05-24 12:10:42', '0000-00-00 00:00:00', 15000.00, 'pending', '1wjJwbgNQfxw5YxlR3Ad', NULL, NULL, '2025-05-24 12:10:42', '2025-05-24 12:10:44');

-- --------------------------------------------------------

--
-- Structure de la table `subscription_notifications`
--

DROP TABLE IF EXISTS `subscription_notifications`;
CREATE TABLE IF NOT EXISTS `subscription_notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subscription_id` int NOT NULL,
  `type` enum('expiry_warning','payment_failed','renewal_success','renewal_failed') NOT NULL,
  `message` text NOT NULL,
  `sent_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscription_id` (`subscription_id`),
  KEY `idx_subscription_notification_type` (`type`,`sent_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `subscription_payments`
--

DROP TABLE IF EXISTS `subscription_payments`;
CREATE TABLE IF NOT EXISTS `subscription_payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subscription_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `payment_reference` varchar(100) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `subscription_id` (`subscription_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `subscription_renewals`
--

DROP TABLE IF EXISTS `subscription_renewals`;
CREATE TABLE IF NOT EXISTS `subscription_renewals` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subscription_id` int NOT NULL,
  `renewal_date` datetime NOT NULL,
  `expiry_date` datetime NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '15000.00',
  `payment_status` enum('pending','completed','failed','expired') DEFAULT 'pending',
  `payment_reference` varchar(100) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `notification_sent` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `subscription_id` (`subscription_id`),
  KEY `idx_subscription_renewal_status` (`payment_status`,`expiry_date`),
  KEY `idx_subscription_renewal_notification` (`notification_sent`,`expiry_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `takencoursebyteacher`
--

DROP TABLE IF EXISTS `takencoursebyteacher`;
CREATE TABLE IF NOT EXISTS `takencoursebyteacher` (
  `id` int NOT NULL AUTO_INCREMENT,
  `courseid` varchar(20) NOT NULL,
  `teacherid` varchar(20) NOT NULL,
  `created_by` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `takencoursebyteacher`
--

INSERT INTO `takencoursebyteacher` (`id`, `courseid`, `teacherid`, `created_by`) VALUES
(1, '4', 'te-123-1', NULL),
(2, '8', 'te-123-1', NULL),
(3, '1', 'te-124-1', NULL),
(4, '2', 'te-124-1', NULL),
(5, '18', 'te-125-1', NULL),
(6, '19', 'te-125-1', NULL),
(7, '11', 'te-125-1', NULL),
(8, '24', 'te-126-1', NULL),
(9, '23', 'te-126-1', NULL),
(10, '22', 'te-126-1', NULL),
(11, '4', 'te-124-1', NULL),
(12, '5', 'te-123-1', NULL),
(13, '6', 'te-125-1', NULL),
(14, '7', 'te-127-1', NULL),
(15, '9', 'te-127-1', NULL),
(16, '10', 'te-127-1', NULL),
(17, '17', 'te-125-1', NULL),
(18, '16', 'te-125-1', NULL),
(19, '15', 'te-125-1', NULL),
(20, '14', 'te-126-1', NULL),
(21, '13', 'te-126-1', NULL),
(22, '12', 'te-126-1', NULL),
(23, '4', 'TE-MET-1596', 'ad-123-1'),
(24, '4', 'TE-MET-1874', 'ad-123-1');

-- --------------------------------------------------------

--
-- Structure de la table `teachers`
--

DROP TABLE IF EXISTS `teachers`;
CREATE TABLE IF NOT EXISTS `teachers` (
  `id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(13) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sex` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dob` date NOT NULL,
  `hiredate` date NOT NULL,
  `salary` double NOT NULL,
  `created_by` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  UNIQUE KEY `id` (`id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `teachers`
--

INSERT INTO `teachers` (`id`, `name`, `password`, `phone`, `email`, `address`, `sex`, `dob`, `hiredate`, `salary`, `created_by`) VALUES
('te-123-1', 'Mr.X', '123', '01711000000', 'mrx@example.com', 'unknown', 'male', '1988-12-23', '2016-01-01', 200000, 'ad-123-0'),
('te-124-1', 'Aziz Khan', '124', '01822376277', 'aziz@gmail,com', 'dhaka', 'male', '1975-12-18', '2001-05-04', 600000, 'ad-123-0'),
('te-125-1', 'Rifat', '258', '01630592385', 'rifat@gmail.com', 'Dhaka', 'Male', '1992-01-26', '2016-05-04', 200000, 'ad-123-0'),
('te-126-1', 'Dipta', '258', '01823568956', 'dipta@gmail.com', 'Savar', 'Male', '1994-01-26', '2016-05-04', 200000, 'ad-123-0'),
('te-127-1', 'Abu saleh', '123', '01765439871', 'abu.saleh@gmail.com', 'Dhaka', 'Male', '1985-02-05', '2016-05-05', 200000, 'ad-123-0'),
('TE-ABD-4064', 'abdou', '123456', '769349169', 'b214bbba@gmail.com', 'ndoutt', 'male', '2001-11-19', '2025-05-21', 20000, 'ad-123-1'),
('TE-MET-1874', 'meth ndiaye', '123456', '778072570', 'g@gmail.com', 'ndoutt', 'male', '2001-10-10', '2025-05-19', 10000, 'ad-123-1'),
('TE-MET-8005', 'meth ndiaye ndiaye', 'meth21', '778072570', 'mouhamedalamine.ndiaye2@unchk.edu.sn', 'ndoutt', 'male', '2001-02-11', '2025-05-24', 100000, 'MET2813');

-- --------------------------------------------------------

--
-- Structure de la table `teacher_absences`
--

DROP TABLE IF EXISTS `teacher_absences`;
CREATE TABLE IF NOT EXISTS `teacher_absences` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `teacher_id` varchar(50) NOT NULL,
  `created_by` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_teacher_date` (`teacher_id`,`date`),
  KEY `created_by` (`created_by`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `teacher_absences`
--

INSERT INTO `teacher_absences` (`id`, `date`, `teacher_id`, `created_by`, `created_at`) VALUES
(1, '2025-05-18', 'TE-MET-7483', 'ad-123-1', '2025-05-18 20:12:04'),
(2, '2025-05-22', 'TE-ABD-4064', 'ad-123-1', '2025-05-22 21:30:19');

-- --------------------------------------------------------

--
-- Structure de la table `teacher_salary_history`
--

DROP TABLE IF EXISTS `teacher_salary_history`;
CREATE TABLE IF NOT EXISTS `teacher_salary_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `teacher_id` varchar(20) NOT NULL,
  `month` int NOT NULL,
  `year` int NOT NULL,
  `base_salary` decimal(10,2) NOT NULL,
  `days_present` int NOT NULL,
  `days_absent` int NOT NULL,
  `final_salary` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `created_by` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `month_year_teacher` (`month`,`year`,`teacher_id`),
  KEY `teacher_id` (`teacher_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `teacher_salary_history`
--

INSERT INTO `teacher_salary_history` (`id`, `teacher_id`, `month`, `year`, `base_salary`, `days_present`, `days_absent`, `final_salary`, `payment_date`, `created_by`) VALUES
(6, 'TE-ABD-4064', 5, 2025, 20000.00, 0, 31, 0.00, '2025-05-22', '0');

-- --------------------------------------------------------

--
-- Structure de la table `time_slots`
--

DROP TABLE IF EXISTS `time_slots`;
CREATE TABLE IF NOT EXISTS `time_slots` (
  `slot_id` int NOT NULL AUTO_INCREMENT,
  `day_number` int NOT NULL COMMENT '1=Lundi, 2=Mardi, etc.',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`slot_id`),
  UNIQUE KEY `unique_slot` (`day_number`,`start_time`,`end_time`)
) ENGINE=MyISAM AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `time_slots`
--

INSERT INTO `time_slots` (`slot_id`, `day_number`, `start_time`, `end_time`, `created_at`) VALUES
(1, 1, '08:00:00', '09:00:00', '2025-05-24 09:13:41'),
(2, 1, '09:00:00', '10:00:00', '2025-05-24 09:13:41'),
(3, 1, '10:15:00', '11:15:00', '2025-05-24 09:13:41'),
(4, 1, '11:15:00', '12:15:00', '2025-05-24 09:13:41'),
(5, 1, '15:00:00', '16:00:00', '2025-05-24 09:13:41'),
(6, 1, '16:00:00', '17:00:00', '2025-05-24 09:13:41'),
(7, 2, '08:00:00', '09:00:00', '2025-05-24 09:13:41'),
(8, 2, '09:00:00', '10:00:00', '2025-05-24 09:13:41'),
(9, 2, '10:15:00', '11:15:00', '2025-05-24 09:13:41'),
(10, 2, '11:15:00', '12:15:00', '2025-05-24 09:13:41'),
(11, 2, '15:00:00', '16:00:00', '2025-05-24 09:13:41'),
(12, 2, '16:00:00', '17:00:00', '2025-05-24 09:13:41'),
(13, 3, '08:00:00', '09:00:00', '2025-05-24 09:13:41'),
(14, 3, '09:00:00', '10:00:00', '2025-05-24 09:13:41'),
(15, 3, '10:15:00', '11:15:00', '2025-05-24 09:13:41'),
(16, 3, '11:15:00', '12:15:00', '2025-05-24 09:13:41'),
(17, 3, '15:00:00', '16:00:00', '2025-05-24 09:13:41'),
(18, 3, '16:00:00', '17:00:00', '2025-05-24 09:13:41'),
(19, 4, '08:00:00', '09:00:00', '2025-05-24 09:13:41'),
(20, 4, '09:00:00', '10:00:00', '2025-05-24 09:13:41'),
(21, 4, '10:15:00', '11:15:00', '2025-05-24 09:13:41'),
(22, 4, '11:15:00', '12:15:00', '2025-05-24 09:13:41'),
(23, 4, '15:00:00', '16:00:00', '2025-05-24 09:13:41'),
(24, 4, '16:00:00', '17:00:00', '2025-05-24 09:13:41'),
(25, 5, '08:00:00', '09:00:00', '2025-05-24 09:13:41'),
(26, 5, '09:00:00', '10:00:00', '2025-05-24 09:13:41'),
(27, 5, '10:15:00', '11:15:00', '2025-05-24 09:13:41'),
(28, 5, '11:15:00', '12:15:00', '2025-05-24 09:13:41'),
(29, 5, '15:00:00', '16:00:00', '2025-05-24 09:13:41'),
(30, 5, '16:00:00', '17:00:00', '2025-05-24 09:13:41'),
(31, 6, '08:00:00', '09:00:00', '2025-05-24 09:13:41'),
(32, 6, '09:00:00', '10:00:00', '2025-05-24 09:13:41'),
(33, 6, '10:15:00', '11:15:00', '2025-05-24 09:13:41'),
(34, 6, '11:15:00', '12:15:00', '2025-05-24 09:13:41'),
(35, 6, '15:00:00', '16:00:00', '2025-05-24 09:13:41'),
(36, 6, '16:00:00', '17:00:00', '2025-05-24 09:13:41');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `userid` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `usertype` varchar(10) NOT NULL,
  UNIQUE KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`userid`, `password`, `usertype`) VALUES
('0', '123456', 'staff'),
('ad-123-0', '123', 'admin'),
('ad-123-1', '$2y$10$ZtVGA.OFGqevA9W7X60s9.2jZnN40Z9McfCwXtxpmpzZryJ5XE5Qm', 'admin'),
('ad-123-2', '123', 'admin'),
('ad-123-3', '123', 'admin'),
('DIO-PA001', 'Alamine', 'parent'),
('MET-PA001', 'Alamine', 'parent'),
('MET2813', 'Alamine', 'admin'),
('PA-123', '123456', 'parent'),
('pa-123-1', '123', 'parent'),
('pa-124-1', '123', 'parent'),
('st-123-1', '123', 'student'),
('st-124-1', '125', 'student'),
('st-125-1', '123', 'student'),
('st001', 'bonjour', 'student'),
('st0112', '$2y$10$CDrSJTSYX7Keu5EU0m00U.CMHe9eS2ByUEyuRPHSfmVHIvWRBGl2y', 'student'),
('st1', '$2y$10$uAm7SXDIsBh4dplQJ4DHa.s0YTLtu/lZ8xHyaAdG/hvWJpL8iYZai', 'student'),
('st200', '$2y$10$x3pXRf3TEMwXtrm14mVL7uZKMFLFWykWx4Nvw9ODDkx9uRXQwgdei', 'student'),
('sta-123-1', 'doule123', 'staff'),
('sta-124-1', 'doule123', 'staff'),
('sta-125-1', 'doule123', 'staff'),
('sta-126-1', 'doule123', 'staff'),
('sta200', '$2y$10$lwyDr9DtIW/2lW6mNIMBVOy5oWd5Y2kLKGmfWkJiKhgHawJ.QL6Re', 'student'),
('STF001ITA', 'doule123', 'staff'),
('te-123-1', '123', 'teacher'),
('te-124-1', '124', 'teacher'),
('te-125-1', '258', 'teacher'),
('te-126-1', '258', 'teacher'),
('te-127-1', '123', 'teacher'),
('TE-ABD-4064', '$2y$10$Gy8eJAyoSEG9sKC8vJRn7e2RF8mB49OQ/.xqNTZa8ouw476H/m69i', 'teacher'),
('TE-MET-1874', '$2y$10$xJIhmJdIyt7g4bEecl97k.678msrDnNzAzzerRYF22f0Y9BcpEC9e', 'teacher'),
('TE-MET-2090', '$2y$10$0Hwm/oK5H3uDz8KOMdankeWsrH3BRKPzlicFN70Dw3M.BUjE95L36', 'teacher'),
('TE-MET-8005', 'meth21', 'teacher');

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `class`
--
ALTER TABLE `class`
  ADD CONSTRAINT `class_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admin` (`id`),
  ADD CONSTRAINT `fk_class_admin` FOREIGN KEY (`created_by`) REFERENCES `admin` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `class_payment_amount`
--
ALTER TABLE `class_payment_amount`
  ADD CONSTRAINT `class_payment_amount_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `class` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `course` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exams_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `admin` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `exam_results`
--
ALTER TABLE `exam_results`
  ADD CONSTRAINT `exam_results_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_results_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `parents`
--
ALTER TABLE `parents`
  ADD CONSTRAINT `parents_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admin` (`id`);

--
-- Contraintes pour la table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admin` (`id`);

--
-- Contraintes pour la table `staff_salary_history`
--
ALTER TABLE `staff_salary_history`
  ADD CONSTRAINT `staff_salary_history_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `student_teacher_course`
--
ALTER TABLE `student_teacher_course`
  ADD CONSTRAINT `student_teacher_course_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_teacher_course_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_teacher_course_ibfk_3` FOREIGN KEY (`course_id`) REFERENCES `course` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_teacher_course_ibfk_4` FOREIGN KEY (`class_id`) REFERENCES `class` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_teacher_course_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `admin` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admin` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admin` (`id`);

--
-- Contraintes pour la table `teacher_salary_history`
--
ALTER TABLE `teacher_salary_history`
  ADD CONSTRAINT `teacher_salary_history_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;
SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
