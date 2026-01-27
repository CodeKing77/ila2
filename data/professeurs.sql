-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mar. 13 jan. 2026 à 05:38
-- Version du serveur : 8.0.31
-- Version de PHP : 8.0.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `ila_publications_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `professeurs`
--

DROP TABLE IF EXISTS `professeurs`;
CREATE TABLE IF NOT EXISTS `professeurs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom_complet` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_general_ci NOT NULL,
  `email_secondaire` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `titre_academique` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `universites` text COLLATE utf8mb4_general_ci,
  `bio` text COLLATE utf8mb4_general_ci,
  `photo_url` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `specialites` text COLLATE utf8mb4_general_ci,
  `telephone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `disponibilite` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `adresse` text COLLATE utf8mb4_general_ci,
  `site_web` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `linkedin_url` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `google_scholar_url` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `orcid_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `professeurs`
--

INSERT INTO `professeurs` (`id`, `nom_complet`, `email`, `email_secondaire`, `password_hash`, `titre_academique`, `universites`, `bio`, `photo_url`, `specialites`, `telephone`, `disponibilite`, `adresse`, `site_web`, `linkedin_url`, `google_scholar_url`, `orcid_id`, `is_active`, `created_at`, `updated_at`, `last_login`) VALUES
(2, 'Prof. N\'Guessan Jéremie KOUADIO', 'n.kouadio@ila.edu', NULL, '$2y$10$YourHashHere', 'Professeur Associé', NULL, 'Expert en langues africaines', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-12-29 15:44:08', '2025-12-29 15:44:08', NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
