-- Elimina il database se esiste già per evitare conflitti o errori di duplicazione
DROP DATABASE IF EXISTS ristorante_db;

-- Crea un nuovo database pulito per il sistema del ristorante
CREATE DATABASE ristorante_db;

-- Seleziona il database appena creato per eseguire le query successive
USE ristorante_db;

-- Crea la tabella principale degli utenti (manager, cuoco, e i vari tavoli)
CREATE TABLE utenti (
    -- ID univoco auto-incrementante per ogni utente
    id_utente INT AUTO_INCREMENT PRIMARY KEY,
    -- Nome utente per il login (deve essere univoco, ad es. 'tavolo1')
    username VARCHAR(50) NOT NULL UNIQUE,
    -- Password per l'autenticazione dell'utente
    password VARCHAR(255) NOT NULL,
    -- Ruolo dell'utente: determina le operazioni consentite nel sistema
    ruolo ENUM('manager','cuoco','tavolo') NOT NULL,

    -- (Campi specifici per i tavoli)
    -- Stato attuale del tavolo per la gestione della sala
    stato ENUM('libero','occupato','riservato') DEFAULT NULL,
    -- Numero di posti a sedere disponibili al tavolo
    posti INT DEFAULT NULL,
    -- Data e ora di inizio della sessione (quando il tavolo effettua il login)
    sessione_inizio DATETIME DEFAULT NULL,
    -- Token del dispositivo per tracciare la sessione attuale (attualmente non usato in modo bloccante)
    device_token VARCHAR(64) DEFAULT NULL,
    -- ID del menu associato a questo tavolo (Foreigh Key aggiunta successivamente)
    id_menu INT DEFAULT NULL
);

-- Crea la tabella per gestire i menu (es. Menu Pranzo, Menu Cena)
CREATE TABLE menu (
    -- ID univoco del menu
    id_menu INT AUTO_INCREMENT PRIMARY KEY,
    -- Nome descrittivo del menu
    nome_menu VARCHAR(50),
    -- L'ID del manager/utente che ha creato o gestisce questo menu
    id_utente INT,
    -- Relazione: ogni menu appartiene a un utente (manager)
    FOREIGN KEY (id_utente) REFERENCES utenti(id_utente)
);

-- Aggiunge la chiave esterna alla tabella utenti per collegare i tavoli ai menu
ALTER TABLE utenti ADD FOREIGN KEY (id_menu) REFERENCES menu(id_menu);

-- Crea la tabella per le categorie applicate agli alimenti (es. Antipasti, Primi)
CREATE TABLE categorie (
    -- ID univoco della categoria
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    -- Nome della categoria
    nome_categoria VARCHAR(50) NOT NULL,
    -- L'ID del menu in cui è contenuta questa categoria
    id_menu INT,
    -- Relazione: ogni categoria appartiene a un menu principale
    FOREIGN KEY (id_menu) REFERENCES menu(id_menu)
);

-- Crea la tabella degli alimenti (i piatti offerti nel ristorante)
CREATE TABLE alimenti (
    -- ID univoco del piatto
    id_alimento INT AUTO_INCREMENT PRIMARY KEY,
    -- Nome mostrato al cliente
    nome_piatto VARCHAR(100) NOT NULL,
    -- Prezzo di vendita del piatto
    prezzo DECIMAL(10,2) NOT NULL,
    -- Descrizione facoltativa che illustra gli ingredienti
    descrizione TEXT,
    -- Stringa contenente gli allergeni separati da virgole (es. 'Glutine,Lattosio')
    lista_allergeni TEXT,
    -- Immagine binaria del piatto (attualmente salvata come BLOB)
    immagine MEDIUMBLOB,
    -- ID della categoria a cui appartiene questo piatto
    id_categoria INT,
    -- Relazione: l'alimento appartiene a una categoria
    FOREIGN KEY (id_categoria) REFERENCES categorie(id_categoria)
);

-- Crea la tabella per gestire l'intestazione degli ordini/comande
CREATE TABLE ordini (
    -- ID identificativo unico per l'ordine complessivo
    id_ordine INT AUTO_INCREMENT PRIMARY KEY,
    -- Stato di preparazione gestito dalla cucina
    stato ENUM('in_attesa','in_preparazione','pronto') DEFAULT 'in_attesa',
    -- Timestamp del momento in cui l'ordine viene inoltrato in cucina
    data_ora DATETIME DEFAULT CURRENT_TIMESTAMP,
    -- L'ID del tavolo (o utente) che ha effettuato questo ordine
    id_utente INT,
    -- Relazione: ogni ordine è legato a chi lo ha effettuato
    FOREIGN KEY (id_utente) REFERENCES utenti(id_utente)
);

-- Crea la tabella di dettaglio (relazione molti-a-molti tra ordini e alimenti)
CREATE TABLE dettaglio_ordini (
    -- Riferimento all'ordine principale
    id_ordine INT,
    -- Riferimento al piatto ordinato
    id_alimento INT,
    -- Quantità richiesta per quello specifico piatto
    quantita INT DEFAULT 1,
    -- Note per lo chef (es. "ben cotto", "senza sale")
    note VARCHAR(255),
    -- La combinazione tra ordine e alimento è la chiave primaria
    PRIMARY KEY (id_ordine, id_alimento),
    -- Se un ordine viene eliminato, cancella anche i suoi dettagli
    FOREIGN KEY (id_ordine) REFERENCES ordini(id_ordine) ON DELETE CASCADE,
    -- Se un alimento viene rimosso, cancella le referenze ad esso
    FOREIGN KEY (id_alimento) REFERENCES alimenti(id_alimento) ON DELETE CASCADE
);

-- --- DATI DI SIMULAZIONE / SEEDING INIZIALE ---

-- Inserimento del Manager principale
INSERT INTO utenti (username, password, ruolo) VALUES ('admin', 'admin', 'manager');
-- Inserimento dell'utente Cucina (Cuoco)
INSERT INTO utenti (username, password, ruolo) VALUES ('cheftest', 'test', 'cuoco');

-- Inserimento di un Menu base collegato al manager (ID 1)
INSERT INTO menu (nome_menu, id_utente) VALUES ('Menu Test', 1);

-- Inserimento dei profili per vari tavoli, assegnando lo stato, il numero di posti e collegandoli al Menu Test (ID 1)
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

-- Creazione delle categorie basilari per il menu Test (ID 1)
INSERT INTO categorie (nome_categoria, id_menu) VALUES
('Antipasti', 1),
('Primi', 1),
('Secondi', 1),
('Dolci', 1);

-- Creazione di un alimento fittizio per testare il database (nella categoria 'Primi' = ID 2)
INSERT INTO alimenti (nome_piatto, prezzo, descrizione, lista_allergeni, immagine, id_categoria)
VALUES ('Carbonara', 12.50, 'Classica pasta alla carbonara con guanciale croccante, uova fresche, pecorino romano DOP e pepe nero macinato al momento.', 'Glutine,Uova,Lattosio', NULL, 2);
