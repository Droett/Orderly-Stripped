# Architettura e Database (Orderly)

Orderly è un'applicazione Web nativa basata su **PHP, MySQL, JavaScript e Vanilla CSS**.
Durante il refactoring, il focus è stato rivolto verso la modularità e la leggibilità, pur eliminando l'eccesso di complessità (boilerplate).

## Il Database MySQL (`ristorante_db`)

Il database è il cuore dell'applicazione e definisce i dati e l'architettura dei permessi.
La connessione avviene globalmente tramite `include/conn.php`.

### Tabelle Principali
1. **`categorie`**
   - Raggruppa i piatti nel menu (Primi, Secondi, Pizze, ecc.).
   - Chiavi primarie, relazionato con `alimenti`.

2. **`alimenti`**
   - Rappresenta i piatti finali. 
   - Contiene un riferimento alla categoria, campi di testo (nome, descrizione, stringa allergeni) e la foto (`immagine` salvata in formato BLOB/LongBlob).

3. **`utenti`**
   - Prima i ruoli erano su tabelle separate, ora tutti gli accessi convivono in `utenti`.
   - Colonne fondamentali: `ruolo` (può essere 'manager', 'cuoco', 'tavolo'), `stato` (libero, occupato, riservato, applicabile ai tavoli), e identificatori `username`, `password`.
   - I `tavoli` detengono sessioni temporanee via `device_token` per accertare che un solo dispositivo possa pilotare l'ordine dal tavolo in dato momento.

4. **`ordini`** e **`dettaglio_ordini`**
   - Tabelle che mantengono lo storico. 
   - Un ordine nasce `in_attesa`, transita in `in_preparazione` ed esce `pronto`.
   - `dettaglio_ordini` espande ogni articolo richiesto, annotandone la specifica variante o nota (se prevista).

5. **`permessi_endpoint`**
   - Tabella di sicurezza fondamentale. Evita l'hardcoding dei permessi nel PHP.
   - Ogni chiamata API è validata sul database. Esempio riga: endpoint `manager/get_tavoli` autorizzato solo al ruolo `manager`. Se il tavolo tenta di inviare a questo endpoint, viene disconnesso/bloccato dal server centrale (middleware).
