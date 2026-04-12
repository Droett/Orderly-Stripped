# Frontend: Dashboard e Viste

Le tre dashboard dell'applicazione (Manager, Tavolo e Cucina) condividono lo stesso stack HTML originato in `dashboards/*.php` e `include/*.php`.  
Tutti includono a catena:  
1. `header.php`  
2. Il proprio layout di corpo grigliato tramite Bootstrap 5. 
3. `include/modals/*` se applicabile.  
4. `footer.php` che re-inietta lo script comune globale.

## 1. `manager.php` (Pannello Controllo Amministratore)
Implementa due "sezioni/pagine" simulanti una *Single Page Application* tramite banale toggling CSS `display:none`:  
- **Tavoli View**: Stampa una griglia asincrona JS che mostra il pallino cromatico per gli status (libero, occupato). Offre bottoni CRUD (create, update, session kick). L'HTML non stampa logicamente niente, aspetta che `manager.js` lo auto-crei da zero intercettando il DB in Polling tramite l'API.
- **Menu View**: Costruita nativamente in PHP. Un mega array `$categorie_array` viene pre-caricato col join del SQL, e poi viene renderizzata una tabella. Contiene i vari bottoni per inserimento.

## 2. `tavolo.php` (Menu Digitale Pubblico/Utente)
Un carrello elettronico stile e-commerce puro.
Usa filtri avanzati. Stampa la galleria con `<div class="item-prodotto">` nascondendo la referenza DB della categoria. 
La logica d'immagine BLOB subisce qui un encoding in `base64` prima dell'output lato template ("`data:image/jpeg;base64,...`"), altrimenti le foto su DB MySQL non verranno mai agganciate dal browser senza header idonei PHP.

Supporta diversi modali di overlay (es: `modalZoom` per i dettagli nutrizionali).

## 3. `cucina.php` (Cruscotto Chef)
La dashboard più spartana, strutturata come due colonne alte tutta altezza: "In Attesa" e "In Preparazione". 
A sua differenza delle altre, non contiene form PHP, ma è solo un simulacro vuoto. Il polling costante di `cucina.js` esegue una `fetch()` ai dati crudi in background e pulisce l'intero tabellone disegnando *card* e array temporali per agevolare il "triage" ordini del cuoco.
