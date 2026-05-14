# Orderly - Guida al Progetto per Project Manager

Orderly è un'applicazione web basata su PHP, MySQL, JavaScript e CSS per la gestione di ordini all'interno di un ristorante tramite tre componenti principali: **Menu Digitale (Tavolo)**, **Pannello Cucina** e **Pannello Manager**.

## Architettura del Progetto
Il progetto segue un'architettura applicativa tradizionale basata su rendering server-side tramite PHP per la gestione della logica e dell'interfaccia utente.

### 1. Database e Base
- **`include/conn.php`**: gestisce la connessione con il db MySQL `ristorante_db`. Tutte le dipendenze passano da qui.
- **`index.php`**: il cancello d'ingresso (Login). Assegna i permessi di sessione.
- **`logout.php`**: distrugge in totale sicurezza i token temporanei della sessione.
- **`include/auth/check_permesso.php`**: controlla se l'utente corrente ha i permessi per accedere a un dashboard specifico.
- **`include/header.php` / `footer.php`**: la struttura "scatola" HTML, in cui passano l'inclusione di Bootstrap e i meta-tag responsivi.

### 2. Dashboard Web
Le viste principali che compongono il fronte utente e amministratore.
- **`dashboards/tavolo.php`**: il menu per il cliente. Tramite questa dashboard, il cliente guarda le categorie, le foto e prezza i prodotti (compresi dettagli allergeni e varianti cucina). È supportato da `js/tavolo.js` per la gestione dell'interfaccia del carrello lato client. 
- **`dashboards/cucina.php`**: la vista operativa ad uso della brigata di cucina, strutturata come una **Kanban board** reale per seguire lo stato delle preparazioni in corso. 
- **`dashboards/manager.php`**: la dashboard direzionale (Backoffice), dove si possono manipolare (aggiungere/modificare/rimuovere) i prodotti, descrizioni, foto, categorie. 

### 3. Elaborazione dei Dati (Backend Processors)
Tutte le operazioni e i servizi di elaborazione dei dati sono stati integrati all'interno dei file delle dashboard stesse tramite richieste POST:
- **Workflow Carrello Cliente e Ordini**: L'aggiunta/rimozione dal carrello e l'invio dell'ordine avvengono all'interno di `dashboards/tavolo.php`, salvando i dati temporanei in sessione e poi scrivendoli in transazioni sicure sul DB.
- **Workflow Cucina**: Il caricamento degli ordini e i cambi di stato (In Attesa -> in Preparazione -> Pronto) sono processati in `dashboards/cucina.php`.
- **Workflow Manageriali**: Le operazioni CRUD per gestire tavoli, categorie e piatti (incluso l'upload delle immagini) sono elaborate nativamente in cima a `dashboards/manager.php`.

### 4. JavaScript e UI
Nella cartella `js/` risiedono gli script necessari alla logica di interfaccia e all'interazione dell'utente:
- `common.js`: gestisce funzioni e interazioni UI comuni su tutte le pagine (come il tema chiaro/scuro e l'inizializzazione delle modali).
- Ogni dashboard possiede assieme al suo file JS omonimo (es: `tavolo.js`, `cucina.js`, `manager.js`) anche un foglio stile CSS in `css/` (es: `tavolo.css`, `cucina.css`, `manager.css`) che gestiscono la responsività, micro-animazioni e dark mode estesa.

## Manutenibilità e Sicurezza
1. **Controllo di Sessione (`$_SESSION['ruolo']`)**: Nessuna dashboard è visibile se forzata via URL. Tutte dispongono del fallback auto-redirigente verso index in mancanza del ruolo corretto.
2. **Prepared Statements in MySQL**: I dati passati ai database, in particolare per categorie e stati, passano spesso tramite statements sicuri per mitigare minacce di SQLInjection.

---
*Progetto analizzato e documentato in data odierna.*