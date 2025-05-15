-- phpMyAdmin SQL Dump
-- version 5.2.1deb1
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : jeu. 15 mai 2025 à 11:18
-- Version du serveur : 10.11.11-MariaDB-0+deb12u1
-- Version de PHP : 8.2.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


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

CREATE TABLE `CLASSE` (
  `id_classe` int(11) NOT NULL,
  `nom_classe` varchar(255) NOT NULL,
  `niveau` enum('1ère Année','2ème Année','3ème Année','4ème Année','5ème Année') DEFAULT NULL,
  `rythme` enum('Alternance','Initial') DEFAULT NULL,
  `numero` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `CLASSE`
--

INSERT INTO `CLASSE` (`id_classe`, `nom_classe`, `niveau`, `rythme`, `numero`) VALUES
(1, '2A1', '2ème Année', 'Alternance', '1'),
(3, '2A2', '2ème Année', 'Alternance', '2'),
(4, '2A3', '2ème Année', 'Alternance', '3'),
(6, '2A5 (aka la classe bien guez)', '2ème Année', 'Alternance', '5'),
(7, '1A2', '1ère Année', 'Alternance', '2'),
(8, '2I1', '2ème Année', 'Initial', '1'),
(9, '2A4', '2ème Année', 'Alternance', '4');

-- --------------------------------------------------------

--
-- Structure de la table `EXAM`
--

CREATE TABLE `EXAM` (
  `id_exam` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `matiere` int(11) NOT NULL,
  `classe` int(11) NOT NULL,
  `date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `EXAM`
--

INSERT INTO `EXAM` (`id_exam`, `titre`, `matiere`, `classe`, `date`) VALUES
(1, 'Analyse de texte', 2, 3, '2025-05-10'),
(10, 'TEST POSITIONNEMENT', 1, 3, '2025-05-20'),
(12, 'Examen Docker', 16, 3, '2025-05-16');

-- --------------------------------------------------------

--
-- Structure de la table `MATIERE`
--

CREATE TABLE `MATIERE` (
  `id_matiere` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `MATIERE`
--

INSERT INTO `MATIERE` (`id_matiere`, `nom`) VALUES
(1, 'Mathématiques'),
(2, 'Français'),
(16, 'Docker'),
(17, 'Azure');

-- --------------------------------------------------------

--
-- Structure de la table `NOTES`
--

CREATE TABLE `NOTES` (
  `id_note` int(11) NOT NULL,
  `note` decimal(4,2) NOT NULL,
  `user` int(11) NOT NULL,
  `exam` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `NOTES`
--

INSERT INTO `NOTES` (`id_note`, `note`, `user`, `exam`) VALUES
(1, 13.00, 1, 10),
(2, 14.00, 2, 10),
(4, 13.00, 2, 12),
(5, 18.00, 4, 12),
(6, 18.00, 4, 10),
(7, 18.00, 5, 10);

-- --------------------------------------------------------

--
-- Structure de la table `PROF`
--

CREATE TABLE `PROF` (
  `id_prof` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `matiere` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `PROF`
--

INSERT INTO `PROF` (`id_prof`, `nom`, `prenom`, `email`, `password`, `matiere`) VALUES
(1, 'El Attar', 'Ahmed', 'mr.ahmed.elattar.pro@gmail.com', '$2y$10$jIn2eY1XF7DEJpsytIHLWu6mF5J6fhlAzIvsOGxZpkQusV.vOxldy', 1),
(2, 'Ngo', 'Mathis', 'mathis.ngoo@gmail.com', '$2y$10$BzH20wFViFEsbSHcgTFg8ezh58.n7Lx9bepbxYomAOPpmI8U4ReCC', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `USER`
--

CREATE TABLE `USER` (
  `id_user` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `classe` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `USER`
--

INSERT INTO `USER` (`id_user`, `nom`, `prenom`, `email`, `password`, `classe`) VALUES
(1, 'Pelcat', 'Arthur', 'apelcat@myges.fr', 'password', 3),
(2, 'Sage', 'William', 'wsage@myges.fr', '$2y$10$CAISmNvb0PAMkSGTU3ae7e900CuRSv4OEltbdAz3WTgUwK6WyMFGy', 3),
(3, 'Theo', 'Przybylski', 'tprzybylski@myges.fr', '$2y$10$7uJ8yuqKcEzEFDIaQqUL9e/FQCIKDsB3Ii0gvEIpq7RIt3zM4571W', 4),
(4, 'El Attar', 'Ahmed', 'aelattar@myges.fr', '$2y$10$LBz9QG0AMBLCujZyWokPu.d1pShfsrecO.H3giN45Un9VGAEwLgsq', 3),
(5, 'Ngo', 'Mathis', 'mngo4@myges.fr', '$2y$10$lJNFC74y13BWKQJnM16DGeHC/ZhCz37esp/voQQHH2PKnW/YS01Ry', 3);

-- --------------------------------------------------------

--
-- Structure de la table `USER_PRIVILEGES`
--

CREATE TABLE `USER_PRIVILEGES` (
  `id_user` int(11) NOT NULL,
  `min_note` decimal(4,2) NOT NULL DEFAULT 18.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `USER_PRIVILEGES`
--

INSERT INTO `USER_PRIVILEGES` (`id_user`, `min_note`, `created_at`) VALUES
(3, 18.00, '2025-05-13 13:11:22'),
(4, 18.00, '2025-05-13 13:11:08'),
(5, 18.00, '2025-05-13 13:11:29');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `CLASSE`
--
ALTER TABLE `CLASSE`
  ADD PRIMARY KEY (`id_classe`);

--
-- Index pour la table `EXAM`
--
ALTER TABLE `EXAM`
  ADD PRIMARY KEY (`id_exam`),
  ADD KEY `matiere` (`matiere`),
  ADD KEY `classe` (`classe`);

--
-- Index pour la table `MATIERE`
--
ALTER TABLE `MATIERE`
  ADD PRIMARY KEY (`id_matiere`);

--
-- Index pour la table `NOTES`
--
ALTER TABLE `NOTES`
  ADD PRIMARY KEY (`id_note`),
  ADD KEY `user` (`user`),
  ADD KEY `exam` (`exam`);

--
-- Index pour la table `PROF`
--
ALTER TABLE `PROF`
  ADD PRIMARY KEY (`id_prof`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `matiere` (`matiere`);

--
-- Index pour la table `USER`
--
ALTER TABLE `USER`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `classe` (`classe`);

--
-- Index pour la table `USER_PRIVILEGES`
--
ALTER TABLE `USER_PRIVILEGES`
  ADD PRIMARY KEY (`id_user`),
  ADD KEY `id_user` (`id_user`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `CLASSE`
--
ALTER TABLE `CLASSE`
  MODIFY `id_classe` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `EXAM`
--
ALTER TABLE `EXAM`
  MODIFY `id_exam` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `MATIERE`
--
ALTER TABLE `MATIERE`
  MODIFY `id_matiere` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT pour la table `NOTES`
--
ALTER TABLE `NOTES`
  MODIFY `id_note` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `PROF`
--
ALTER TABLE `PROF`
  MODIFY `id_prof` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `USER`
--
ALTER TABLE `USER`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `EXAM`
--
ALTER TABLE `EXAM`
  ADD CONSTRAINT `EXAM_ibfk_1` FOREIGN KEY (`matiere`) REFERENCES `MATIERE` (`id_matiere`),
  ADD CONSTRAINT `EXAM_ibfk_2` FOREIGN KEY (`classe`) REFERENCES `CLASSE` (`id_classe`);

--
-- Contraintes pour la table `NOTES`
--
ALTER TABLE `NOTES`
  ADD CONSTRAINT `NOTES_ibfk_1` FOREIGN KEY (`user`) REFERENCES `USER` (`id_user`),
  ADD CONSTRAINT `NOTES_ibfk_2` FOREIGN KEY (`exam`) REFERENCES `EXAM` (`id_exam`);

--
-- Contraintes pour la table `PROF`
--
ALTER TABLE `PROF`
  ADD CONSTRAINT `PROF_ibfk_1` FOREIGN KEY (`matiere`) REFERENCES `MATIERE` (`id_matiere`);

--
-- Contraintes pour la table `USER`
--
ALTER TABLE `USER`
  ADD CONSTRAINT `USER_ibfk_1` FOREIGN KEY (`classe`) REFERENCES `CLASSE` (`id_classe`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
