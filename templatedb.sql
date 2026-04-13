DROP DATABASE IF EXISTS ristorante_db;
CREATE DATABASE ristorante_db;
USE ristorante_db;

-- TABELLA UTENTI (unifica manager, cuochi e tavoli)
CREATE TABLE utenti (
    id_utente INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    ruolo ENUM('manager','cuoco','tavolo') NOT NULL,
    -- Campi usati solo quando ruolo = 'tavolo'
    stato ENUM('libero','occupato','riservato') DEFAULT NULL,
    posti INT DEFAULT NULL,
    sessione_inizio DATETIME DEFAULT NULL,
    device_token VARCHAR(64) DEFAULT NULL,
    id_menu INT DEFAULT NULL
);

-- TABELLA MENU
CREATE TABLE menu (
    id_menu INT AUTO_INCREMENT PRIMARY KEY,
    nome_menu VARCHAR(50),
    id_utente INT,
    FOREIGN KEY (id_utente) REFERENCES utenti(id_utente)
);

-- Aggiungiamo la FK di id_menu in utenti dopo che menu esiste
ALTER TABLE utenti ADD FOREIGN KEY (id_menu) REFERENCES menu(id_menu);

-- TABELLA CATEGORIE
CREATE TABLE categorie (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nome_categoria VARCHAR(50) NOT NULL,
    id_menu INT,
    FOREIGN KEY (id_menu) REFERENCES menu(id_menu)
);

-- TABELLA ALIMENTI
CREATE TABLE alimenti (
    id_alimento INT AUTO_INCREMENT PRIMARY KEY,
    nome_piatto VARCHAR(100) NOT NULL,
    prezzo DECIMAL(10,2) NOT NULL,
    descrizione TEXT,
    lista_allergeni TEXT,
    immagine MEDIUMBLOB,
    id_categoria INT,
    FOREIGN KEY (id_categoria) REFERENCES categorie(id_categoria)
);

-- TABELLA ORDINI
CREATE TABLE ordini (
    id_ordine INT AUTO_INCREMENT PRIMARY KEY,
    stato ENUM('in_attesa','in_preparazione','pronto') DEFAULT 'in_attesa',
    data_ora DATETIME DEFAULT CURRENT_TIMESTAMP,
    id_utente INT,
    FOREIGN KEY (id_utente) REFERENCES utenti(id_utente)
);

-- TABELLA DETTAGLIO ORDINI
CREATE TABLE dettaglio_ordini (
    id_ordine INT,
    id_alimento INT,
    quantita INT DEFAULT 1,
    note VARCHAR(255),
    PRIMARY KEY (id_ordine, id_alimento),
    FOREIGN KEY (id_ordine) REFERENCES ordini(id_ordine) ON DELETE CASCADE,
    FOREIGN KEY (id_alimento) REFERENCES alimenti(id_alimento) ON DELETE CASCADE
);


-- --- DATI DI TEST ---

-- Utenti: manager, cuoco e tavoli
INSERT INTO utenti (username, password, ruolo) VALUES ('admin', 'admin', 'manager');
INSERT INTO utenti (username, password, ruolo) VALUES ('cheftest', 'test', 'cuoco');

-- Menu base (creato dall'admin, id_utente = 1)
INSERT INTO menu (nome_menu, id_utente) VALUES ('Menu Test', 1);

-- Tavoli di test (ruolo = 'tavolo', con campi specifici)
INSERT INTO utenti (username, password, ruolo, stato, posti, id_menu) VALUES
('tavolotest', 'test', 'tavolo', 'libero', 4, 1),
('tavolo1', '1234', 'tavolo', 'libero', 2, 1),
('tavolo2', '1234', 'tavolo', 'occupato', 4, 1),
('tavolo3', '1234', 'tavolo', 'riservato', 6, 1),
('tavolo4', '1234', 'tavolo', 'libero', 4, 1),
('tavolo5', '1234', 'tavolo', 'occupato', 2, 1),
('tavolo6', '1234', 'tavolo', 'libero', 8, 1),
('tavolo7', '1234', 'tavolo', 'riservato', 4, 1),
('tavolo8', '1234', 'tavolo', 'libero', 4, 1);

-- Categorie
INSERT INTO categorie (nome_categoria, id_menu) VALUES
('Antipasti', 1),
('Primi', 1),
('Secondi', 1),
('Dolci', 1);

-- Piatto di test
INSERT INTO alimenti (nome_piatto, prezzo, descrizione, lista_allergeni, immagine, id_categoria)
VALUES ('Carbonara', 12.50, 'Classica pasta alla carbonara con guanciale croccante, uova fresche, pecorino romano DOP e pepe nero macinato al momento.', 'Glutine,Uova,Lattosio', NULL, 2);
