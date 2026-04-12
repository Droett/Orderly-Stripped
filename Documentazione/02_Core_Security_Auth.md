# Sicurezza, Core e Autenticazioni

Questa sezione illustra i moduli radice dell'applicazione utilizzati per connettersi, proteggere endpoint e inizializzare sessioni.

## La cartella `include/`

- **`conn.php`**: file base per la connessione. Imposta le credenziali MySQL e restituisce l'oggetto `$conn` (`mysqli`).
- **`constants.php`**: Inizializza macro-variabili statiche e globali (es. array `$ALLERGENI`) a cui il manager e i clienti si appoggiano per coerenza di UI. Evita la triplicazione dei dati nel DB e nei file modali.
- **`header.php`** / **`footer.php`**: L'ossatura base HTML per le dashboard. Contengono i link Bootstrap e un piccolo script vanilla che calcola il tema Light/Dark mode preventivamente (senza creare Flash bianchi a schermo) appoggiandosi a `localStorage`.

## Sistema di Autenticazione: `check_permesso.php`

Il pilastro della sicurezza. Fornisce la funzione globale `verificaPermesso($conn, $endpoint_richiesto);`.
Valuta `$_SESSION['ruolo']` e il nome/azione dell'endpoint che cerca accessi, estraendoli direttamente dalle restrizioni dinamiche nella tabella MySQL `permessi_endpoint`. Se non trova un match di tipo true/false, uccide il runtime con `die()`.

## Flusso Accesso (`index.php`) e Disconnessione (`logout.php`)

**Al varco utente (`index.php`)**:
Il sistema interpreta il `$ruolo`. 
- Se è manager o cuoco, carica le sessioni e invia direttamente a destinazione (cruscotti `dashboards/`).
- Se è `tavolo`, c'è un trucco elaborato:
  1. Si assicura che il token scambiato dal form corrisponda, evitando furbetti da scannerizzazioni parallele.
  2. Genera e distribuisce al Client un `setcookie('device_token_XYZ')`. Contemporaneamente blocca il tavolo sulla colonna DB (`stato='occupato'`), vincolando l'intera sessione asincrona al dispositivo del cliente.

**Uscita (`logout.php`)**:
Esegue un classico `session_destroy()`. Distrugge ogni referenza `$device_token` se sei un tavolo e rimette lo stato fisico a 'libero' cosicché l'admin possa dare il via a nuovi avventori al tavolo.
