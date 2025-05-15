-- Script SQL pour Azure SQL Database avec table STUDENT et colonne student_id

-- Étape 1 : Supprimer les tables existantes
DECLARE @sql NVARCHAR(MAX) = '';
SELECT @sql += 'ALTER TABLE ' + QUOTENAME(TABLE_SCHEMA) + '.' + QUOTENAME(TABLE_NAME) + ' DROP CONSTRAINT ' + QUOTENAME(CONSTRAINT_NAME) + ';'
FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
WHERE CONSTRAINT_TYPE = 'FOREIGN KEY';
EXEC sp_executesql @sql;

SET @sql = '';
SELECT @sql += 'DROP TABLE ' + QUOTENAME(TABLE_SCHEMA) + '.' + QUOTENAME(TABLE_NAME) + ';'
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_TYPE = 'BASE TABLE';
EXEC sp_executesql @sql;

-- Étape 2 : Créer les tables
CREATE TABLE CLASSE (
    id_classe INT NOT NULL,
    nom_classe NVARCHAR(255) NOT NULL,
    niveau NVARCHAR(50) NULL,
    rythme NVARCHAR(50) NULL,
    numero NVARCHAR(50) NOT NULL,
    PRIMARY KEY (id_classe),
    CONSTRAINT CHK_niveau CHECK (niveau IN (N'1ère Année', N'2ème Année', N'3ème Année', N'4ème Année', N'5ème Année')),
    CONSTRAINT CHK_rythme CHECK (rythme IN (N'Alternance', N'Initial'))
);

CREATE TABLE MATIERE (
    id_matiere INT NOT NULL,
    nom NVARCHAR(255) NOT NULL,
    PRIMARY KEY (id_matiere)
);

CREATE TABLE EXAM (
    id_exam INT NOT NULL,
    titre NVARCHAR(255) NOT NULL,
    matiere INT NOT NULL,
    classe INT NOT NULL,
    date DATE NULL,
    PRIMARY KEY (id_exam),
    CONSTRAINT FK_exam_matiere FOREIGN KEY (matiere) REFERENCES MATIERE(id_matiere),
    CONSTRAINT FK_exam_classe FOREIGN KEY (classe) REFERENCES CLASSE(id_classe)
);

CREATE TABLE STUDENT (
    id_user INT NOT NULL,
    nom NVARCHAR(255) NOT NULL,
    prenom NVARCHAR(255) NOT NULL,
    email NVARCHAR(255) NOT NULL,
    password NVARCHAR(255) NOT NULL,
    classe INT NULL,
    PRIMARY KEY (id_user),
    CONSTRAINT UK_email UNIQUE (email),
    CONSTRAINT FK_student_classe FOREIGN KEY (classe) REFERENCES CLASSE(id_classe)
);

CREATE TABLE NOTES (
    id_note INT NOT NULL,
    note DECIMAL(4,2) NOT NULL,
    student_id INT NOT NULL,
    exam INT NOT NULL,
    PRIMARY KEY (id_note),
    CONSTRAINT FK_notes_student FOREIGN KEY (student_id) REFERENCES STUDENT(id_user),
    CONSTRAINT FK_notes_exam FOREIGN KEY (exam) REFERENCES EXAM(id_exam)
);

CREATE TABLE PROF (
    id_prof INT NOT NULL,
    nom NVARCHAR(255) NOT NULL,
    prenom NVARCHAR(255) NOT NULL,
    email NVARCHAR(255) NOT NULL,
    password NVARCHAR(255) NOT NULL,
    matiere INT NULL,
    PRIMARY KEY (id_prof),
    CONSTRAINT UK_prof_email UNIQUE (email),
    CONSTRAINT FK_prof_matiere FOREIGN KEY (matiere) REFERENCES MATIERE(id_matiere)
);

-- Étape 3 : Configurer les séquences pour simuler AUTO_INCREMENT
CREATE SEQUENCE seq_classe START WITH 9 INCREMENT BY 1;
ALTER TABLE CLASSE
ADD CONSTRAINT DF_classe_id DEFAULT (NEXT VALUE FOR seq_classe) FOR id_classe;

CREATE SEQUENCE seq_exam START WITH 11 INCREMENT BY 1;
ALTER TABLE EXAM
ADD CONSTRAINT DF_exam_id DEFAULT (NEXT VALUE FOR seq_exam) FOR id_exam;

CREATE SEQUENCE seq_matiere START WITH 9 INCREMENT BY 1;
ALTER TABLE MATIERE
ADD CONSTRAINT DF_matiere_id DEFAULT (NEXT VALUE FOR seq_matiere) FOR id_matiere;

CREATE SEQUENCE seq_notes START WITH 2 INCREMENT BY 1;
ALTER TABLE NOTES
ADD CONSTRAINT DF_notes_id DEFAULT (NEXT VALUE FOR seq_notes) FOR id_note;

CREATE SEQUENCE seq_prof START WITH 3 INCREMENT BY 1;
ALTER TABLE PROF
ADD CONSTRAINT DF_prof_id DEFAULT (NEXT VALUE FOR seq_prof) FOR id_prof;

CREATE SEQUENCE seq_user START WITH 2 INCREMENT BY 1;
ALTER TABLE STUDENT
ADD CONSTRAINT DF_user_id DEFAULT (NEXT VALUE FOR seq_user) FOR id_user;

-- Étape 4 : Insérer les données
INSERT INTO CLASSE (id_classe, nom_classe, niveau, rythme, numero)
VALUES
    (1, N'2A1', N'2ème Année', N'Alternance', N'1'),
    (3, N'2A2', N'2ème Année', N'Alternance', N'2'),
    (4, N'2A3', N'2ème Année', N'Alternance', N'3'),
    (5, N'2A4', N'2ème Année', N'Alternance', N'4'),
    (6, N'2A5 (aka la classe bien guez)', N'2ème Année', N'Alternance', N'5'),
    (7, N'1A2', N'1ère Année', N'Alternance', N'2'),
    (8, N'2I1', N'2ème Année', N'Initial', N'1');

INSERT INTO MATIERE (id_matiere, nom)
VALUES
    (1, N'Mathématiques'),
    (2, N'Français');

INSERT INTO EXAM (id_exam, titre, matiere, classe, date)
VALUES
    (1, N'Analyse de texte', 2, 3, '2025-05-10'),
    (10, N'TEST POSITIONNEMENT', 1, 3, '2025-05-20');

INSERT INTO STUDENT (id_user, nom, prenom, email, password, classe)
VALUES
    (1, N'Pelcat', N'Arthur', N'arthur.pelcat@gmail.com', N'password', 3);

INSERT INTO NOTES (id_note, note, student_id, exam)
VALUES
    (1, 18.00, 1, 10);

INSERT INTO PROF (id_prof, nom, prenom, email, password, matiere)
VALUES
    (1, N'El Attar', N'Ahmed', N'mr.ahmed.elattar.pro@gmail.com', N'$2y$10$rHHPFQ/0FygLxeR2i0xWQemvB2r5EWtecw2nXyb6Z.dXvgrzr35WW', 1),
    (2, N'Ngo', N'Mathis', N'mathis.ngoo@gmail.com', N'$2y$10$BzH20wFViFEsbSHcgTFg8ezh58.n7Lx9bepbxYomAOPpmI8U4ReCC', NULL);