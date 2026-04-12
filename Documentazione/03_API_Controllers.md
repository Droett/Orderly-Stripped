# Controller API

A seguito dell'ultimo, drastico refactoring, 19 file obsoleti sono stati spazzati via, sostituiti da tre direttrici (controller) modulari, uno per ogni "ruolo/dashboard": `manager_api.php`, `tavolo_api.php`, `cucina_api.php`.

Questo approccio DRY incapsula in alto i controlli identici (`session_start()`, sicurezza db `$conn`, controlli permessi tramite la funzione astratta `verificaPermesso()`), per poi instradare l'esecuzione tramite un costrutto `switch`. L'esito dipende dal parametro HTTP GET: `?action=X`.

## 1. `manager_api.php`
Contiene tutto il backend per l'amministrazione e i pannelli di cruscotto del Manager.
Possibili percorsi `action`:
- `get_tavoli`: Stampa un array JSON contenente le righe anagrafiche dei tavoli (+ numero ordini attivi al netto del check).
- `aggiungi_tavolo` e `elimina_tavolo`: Modifiche alla tabella `utenti` che rimuovono anche le vecchie history prima di abbattere la riga del tavolo.
- `cambia_stato_tavolo`: Switch di stato ("libero", "riservato", "occupato").
- `termina_sessione`: Kicka i token virtuali del table da JS in tempo reale (libero).
- Operazioni Menu (`aggiungi_categoria`, `elimina_categoria`, `aggiungi_piatto`, `modifica_piatto`, `elimina_piatto`): Modificano `alimenti` e `categorie`. Particolarità importante è che i *piatti inviati* supportano un allegato `multipart/form-data` su cui il controller legge temporaneamente `$_FILES['immagine']` iniettando lo stream binario direttamente sulla riga `LONGBLOB` del DB. 

## 2. `tavolo_api.php`
API per un tavolo seduto che scorre il Digital Menu.
Possibili percorsi `action`:
- `get_carrello`: Interroga se c'è memoria volatile. Non scrive Database (scritto globalmente sul namespace o un'altra utility? In questo caso i Carrelli non sono scritti in DB, lo stack RAM lo fa la dashboard Javascript `tavolo.js` client-side e ri-richiede ad ogni aggiornamento).
- `aggiungi_al_carrello` e `rimuovi_dal_carrello`: (Sessioni lato server virtuali).
- `invia_ordine`: Scarica nel DB `ordini` e propaga su `dettaglio_ordini` tutte le specifiche e quantità in JSON string passata dal Javascript, poi avvia lo status a `in_attesa` per i monitor delle cucine.
- `leggi_ordini_tavolo`: Storico passato ad uso UI del ticket al tavolo.
- `verifica_sessione`: Usato dal background poller ad ogni refresh della pagina client per intercettare dal Server se l'amministratore (Manager) lo ha sloggato disattivando lo slot Token DB.

## 3. `cucina_api.php`
Molto snello. Unico compito gestire la board in stile "Kanban" in tempo reale (polling dal Frontend).
Possibili percorsi `action`:
- `leggi_ordini_cucina`: Sputa i task attivi (`in_attesa`, `in_preparazione`) e i totali aggregati per pezzo e per tavolo.
- `cambia_stato_ordine`: Invia da `in_attesa` a `in_preparazione` o chiude definitivamente il ticket come `pronto` sgomberando lo stream per gli chef.
