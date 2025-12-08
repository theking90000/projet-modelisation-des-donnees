CREATE TABLE Utilisateur
(
    email VARCHAR(100) PRIMARY KEY,
    nom VARCHAR(50),
    prenom VARCHAR(50),
    mot_de_passe VARCHAR(200),
    date_creation DATE
);

CREATE TABLE Membre_Portfolio
(
    email VARCHAR(100),
    id_portfolio INT,
    niveau_acces INT,
    PRIMARY KEY(email, id_portfolio)
);

CREATE TABLE Instrument_Financier
(
    isin CHAR(12) PRIMARY KEY,
    nom VARCHAR(50),
    `type` VARCHAR(10),
    symbole VARCHAR(20),
    taux DECIMAL(18, 9),
    date_emission DATE,
    date_echeance DATE,
    couple_devise VARCHAR(15),
    id_bourse VARCHAR(255),
    code_pays CHAR(2),
    numero_entreprise VARCHAR(20),
    pays_entreprise CHAR(2),
    code_devise CHAR(3)
);

/* 
Selon Google, pays le plus long compte 51 caractères (Royaume Uni de Grande Bretagne et d'Irlande du Nord) 
bon c'est overkill mais on sait jamais...
Si pas, on part sur genre 25 caractères, et quand un utilisateur encode un pays,
on propose un menu déroulant pour forcer le nom qu'on veut et éviter problèmes 
*/

CREATE TABLE Pays
(
    code CHAR(2) PRIMARY KEY,
    nom VARCHAR(51)
);

CREATE TABLE Bourse
(
    id VARCHAR(255) PRIMARY KEY,
    nom VARCHAR(255),
    ville VARCHAR(50),
    fuseau_horaire VARCHAR(50),
    heure_ouverture TIME,
    heure_fermeture TIME,
    code_pays CHAR(2)
);

CREATE TABLE Entreprise
(
    numero VARCHAR(20),
    code_pays CHAR(2),
    nom VARCHAR(50),
    secteur VARCHAR(50),
    PRIMARY KEY (numero, code_pays)
);

CREATE TABLE Cours
(
    isin CHAR(12),
    `date` DATE,
    valeur_ouverture DECIMAL(18, 9),
    valeur_fermeture DECIMAL(18, 9),
    valeur_maximale DECIMAL(18, 9),
    valeur_minimale DECIMAL(18, 9),
    volume INT,
    PRIMARY KEY(isin, `date`, heure)
);

CREATE TABLE Portfolio
(
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50),
    `description` VARCHAR(255),
    code_devise CHAR(3),
    date_creation DATE
);

CREATE TABLE `Transaction`
(
    id INT AUTO_INCREMENT,
    id_portfolio INT,
    isin CHAR(12),
    email_utilisateur VARCHAR(100),
    `type` CHAR(5),
    `date` DATE,
    heure TIME,
    quantite DECIMAL(18, 9),
    valeur_devise_portfolio DECIMAL(18, 9),
    valeur_devise_instrument DECIMAL(18, 9),
    frais DECIMAL(18, 9),
    taxes DECIMAL(18, 9),
    PRIMARY KEY (id, id_portfolio)
);

CREATE TABLE Devise
(
    code CHAR(3) PRIMARY KEY,
    nom VARCHAR(50),
    symbole VARCHAR(5)
);

-- Nomenclature pour le nom des contraintes : table d'où vient la clé _ table qui fait la référence

ALTER TABLE Membre_Portfolio
ADD CONSTRAINT utilisateur_membre FOREIGN KEY (email) REFERENCES Utilisateur (email);

ALTER TABLE Membre_Portfolio
ADD CONSTRAINT portfolio_membre FOREIGN KEY (id_portfolio) REFERENCES Portfolio (id);

ALTER TABLE Instrument_Financier
ADD CONSTRAINT bourse_instrument FOREIGN KEY (id_bourse) REFERENCES Bourse (id);

ALTER TABLE Instrument_Financier
ADD CONSTRAINT pays_instrument FOREIGN KEY (code_pays) REFERENCES Pays (code);

ALTER TABLE Instrument_Financier
ADD CONSTRAINT entreprise_instrument FOREIGN KEY (numero_entreprise, pays_entreprise) REFERENCES Entreprise (numero, code_pays);

ALTER TABLE Instrument_Financier
ADD CONSTRAINT devise_instrument FOREIGN KEY (code_devise) REFERENCES Devise (code);

ALTER TABLE Bourse
ADD CONSTRAINT pays_bourse FOREIGN KEY (code_pays) REFERENCES Pays (code);

ALTER TABLE Entreprise
ADD CONSTRAINT pays_entreprise FOREIGN KEY (code_pays) REFERENCES Pays (code);

ALTER TABLE Cours
ADD CONSTRAINT instrument_cours FOREIGN KEY (isin) REFERENCES Instrument_Financier (isin);

ALTER TABLE Portfolio
ADD CONSTRAINT devise_portfolio FOREIGN KEY (code_devise) REFERENCES Devise (code);

ALTER TABLE `Transaction`
ADD CONSTRAINT portfolio_transaction FOREIGN KEY (id_portfolio) REFERENCES Portfolio (id);

ALTER TABLE `Transaction`
ADD CONSTRAINT instrument_transaction FOREIGN KEY (isin) REFERENCES Instrument_Financier (isin);

ALTER TABLE `Transaction`
ADD CONSTRAINT utilisateur_transaction FOREIGN KEY (email_utilisateur) REFERENCES Utilisateur (email);

-- Ajout des contraintes "ON DELETE CASCADE"

ALTER TABLE Membre_Portfolio DROP CONSTRAINT portfolio_membre;

ALTER TABLE Membre_Portfolio ADD CONSTRAINT portfolio_membre FOREIGN KEY (id_portfolio) REFERENCES Portfolio (id)
ON DELETE CASCADE;

ALTER TABLE Transaction DROP CONSTRAINT portfolio_transaction;

ALTER TABLE Transaction ADD CONSTRAINT portfolio_transaction FOREIGN KEY (id_portfolio) REFERENCES Portfolio (id)
ON DELETE CASCADE;