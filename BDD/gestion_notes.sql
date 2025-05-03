-- phpMyAdmin SQL Dump
-- version 5.2.1deb1
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : sam. 03 mai 2025 à 19:22
-- Version du serveur : 10.11.11-MariaDB-0+deb12u1
-- Version de PHP : 8.2.28
SET
  SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

START TRANSACTION;

SET
  time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;

/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;

/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;

/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `gestion_notes`
--
-- --------------------------------------------------------
--
-- Structure de la table `CLASSE`
--
CREATE TABLE
  `CLASSE` (
    `id_classe` int (11) NOT NULL,
    `nom_classe` varchar(255) NOT NULL,
    `niveau` enum (
      '1ère Année',
      '2ème Année',
      '3ème Année',
      '4ème Année',
      '5ème Année'
    ) DEFAULT NULL,
    `rythme` enum ('Alternance', 'Inital') DEFAULT NULL,
    `numero` varchar(50) NOT NULL
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Déchargement des données de la table `CLASSE`
--
INSERT INTO
  `CLASSE` (
    `id_classe`,
    `nom_classe`,
    `niveau`,
    `rythme`,
    `numero`
  )
VALUES
  (1, '2A1', NULL, 'Alternance', '1'),
  (3, '2A2', NULL, 'Alternance', '2'),
  (4, '2A3', NULL, 'Alternance', '3'),
  (5, '2A4', NULL, 'Alternance', '4');

-- --------------------------------------------------------
--
-- Structure de la table `EXAM`
--
CREATE TABLE
  `EXAM` (
    `id_exam` int (11) NOT NULL,
    `titre` varchar(255) NOT NULL,
    `matiere` int (11) NOT NULL,
    `classe` int (11) NOT NULL
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Structure de la table `MATIERE`
--
CREATE TABLE
  `MATIERE` (
    `id_matiere` int (11) NOT NULL,
    `nom` varchar(255) NOT NULL
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Déchargement des données de la table `MATIERE`
--
INSERT INTO
  `MATIERE` (`id_matiere`, `nom`)
VALUES
  (1, 'Mathématiques'),
  (2, 'Français');

-- --------------------------------------------------------
--
-- Structure de la table `NOTES`
--
CREATE TABLE
  `NOTES` (
    `id_note` int (11) NOT NULL,
    `note` decimal(4, 2) NOT NULL,
    `user` int (11) NOT NULL,
    `exam` int (11) NOT NULL
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Structure de la table `PROF`
--
CREATE TABLE
  `PROF` (
    `id_prof` int (11) NOT NULL,
    `nom` varchar(255) NOT NULL,
    `prenom` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `password` varchar(255) NOT NULL,
    `matiere` int (11) DEFAULT NULL
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Déchargement des données de la table `PROF`
--
INSERT INTO
  `PROF` (
    `id_prof`,
    `nom`,
    `prenom`,
    `email`,
    `password`,
    `matiere`
  )
VALUES
  (
    1,
    'El Attar',
    'Ahmed',
    'mr.ahmed.elattar.pro@gmail.com',
    '$2y$10$rHHPFQ/0FygLxeR2i0xWQemvB2r5EWtecw2nXyb6Z.dXvgrzr35WW',
    1
  );

-- --------------------------------------------------------
--
-- Structure de la table `USER`
--
CREATE TABLE
  `USER` (
    `id_user` int (11) NOT NULL,
    `nom` varchar(255) NOT NULL,
    `prenom` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `password` varchar(255) NOT NULL,
    `classe` int (11) DEFAULT NULL
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

--
-- Index pour les tables déchargées
--
--
-- Index pour la table `CLASSE`
--
ALTER TABLE `CLASSE` ADD PRIMARY KEY (`id_classe`);

--
-- Index pour la table `EXAM`
--
ALTER TABLE `EXAM` ADD PRIMARY KEY (`id_exam`),
ADD KEY `matiere` (`matiere`),
ADD KEY `classe` (`classe`);

--
-- Index pour la table `MATIERE`
--
ALTER TABLE `MATIERE` ADD PRIMARY KEY (`id_matiere`);

--
-- Index pour la table `NOTES`
--
ALTER TABLE `NOTES` ADD PRIMARY KEY (`id_note`),
ADD KEY `user` (`user`),
ADD KEY `exam` (`exam`);

--
-- Index pour la table `PROF`
--
ALTER TABLE `PROF` ADD PRIMARY KEY (`id_prof`),
ADD UNIQUE KEY `email` (`email`),
ADD KEY `matiere` (`matiere`);

--
-- Index pour la table `USER`
--
ALTER TABLE `USER` ADD PRIMARY KEY (`id_user`),
ADD UNIQUE KEY `email` (`email`),
ADD KEY `classe` (`classe`);

--
-- AUTO_INCREMENT pour les tables déchargées
--
--
-- AUTO_INCREMENT pour la table `CLASSE`
--
ALTER TABLE `CLASSE` MODIFY `id_classe` int (11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 6;

--
-- AUTO_INCREMENT pour la table `EXAM`
--
ALTER TABLE `EXAM` MODIFY `id_exam` int (11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `MATIERE`
--
ALTER TABLE `MATIERE` MODIFY `id_matiere` int (11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 8;

--
-- AUTO_INCREMENT pour la table `NOTES`
--
ALTER TABLE `NOTES` MODIFY `id_note` int (11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `PROF`
--
ALTER TABLE `PROF` MODIFY `id_prof` int (11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 2;

--
-- AUTO_INCREMENT pour la table `USER`
--
ALTER TABLE `USER` MODIFY `id_user` int (11) NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--
--
-- Contraintes pour la table `EXAM`
--
ALTER TABLE `EXAM` ADD CONSTRAINT `EXAM_ibfk_1` FOREIGN KEY (`matiere`) REFERENCES `MATIERE` (`id_matiere`),
ADD CONSTRAINT `EXAM_ibfk_2` FOREIGN KEY (`classe`) REFERENCES `CLASSE` (`id_classe`);

--
-- Contraintes pour la table `NOTES`
--
ALTER TABLE `NOTES` ADD CONSTRAINT `NOTES_ibfk_1` FOREIGN KEY (`user`) REFERENCES `USER` (`id_user`),
ADD CONSTRAINT `NOTES_ibfk_2` FOREIGN KEY (`exam`) REFERENCES `EXAM` (`id_exam`);

--
-- Contraintes pour la table `PROF`
--
ALTER TABLE `PROF` ADD CONSTRAINT `PROF_ibfk_1` FOREIGN KEY (`matiere`) REFERENCES `MATIERE` (`id_matiere`);

--
-- Contraintes pour la table `USER`
--
ALTER TABLE `USER` ADD CONSTRAINT `USER_ibfk_1` FOREIGN KEY (`classe`) REFERENCES `CLASSE` (`id_classe`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;

/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;

/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;