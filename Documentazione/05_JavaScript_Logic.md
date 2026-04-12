# Logiche JavaScript e Staging dell'Interfaccia

Le dashboard godono di interattività "Live" asincrona e responsiva supportata dalla directory `js/`.

## 1. `common.js`
Raccoglie i tool universali:
- Sincronizzazione visiva `theme_sync` interfacciata coi toggle per la Dark/Light in localStorage.
- Funzione globale `mostraToast(msg, isError)`. Consente di invocare pop-up stile Bootstrap toast cross-pagina uniformando gli output sia nel manager che per le notifiche ai clienti ("Aggiunto al carrello!").

## 2. `manager.js`
Gestisce la ricarica dei tavoli ad intervalli cadenzati (polling 10000ms), rimpiazzando l'intero set DOM `tavoli-grid` ad ogni tick in base a ciò che dice l'API.
Per snellire le ripetitive chiamate API, espone la nuova utility DRY:
- `managerApiCall(action, formData, successMsg, closeModalId)`: Funzione generica che fa il fetch dal controller `.php?action={}`, chiude un modale specifico su esito ed evoca un re-rendering forzato e fresco visualizzando esiti puliti o intercettando fallimenti lato server.

## 3. `tavolo.js`
È il file javascript più corposo (motore eCommerce). Gestisce il dizionario o gettone JS globale della spesa (`let carrello`). 
Esso disaccoppia il Database e la UI locale del Cliente in due step, tenuti uniformi da logiche di Sync:
- `syncCartBackend(id_piatto, quantita)`: La funzione snella helper che inietta i differenziali JSON (delta matematici) alle API, e capisce in automatico se un alimento è stato aggiunto via `?action=aggiungi_al_carrello` o sottratto fino a decrementarlo fisicamente dalle code database.
- `aggiornaUI()` e `aggiornaModale()`. Metodi che riscrivono i talloncini CSS in real-time, i quantitativi scritti sopra le anteprime delle foto nell'applicazione e il totale "virtualizzato" della spesa stimata del consumatore prima della firma.

## 4. `cucina.js`
Polling ossessivo ai ticket pronti per il personale (`caricaOrdini()`). Scompone gli status in due stream (Attesa e Preparazione). Se non ci sono tick aperti, disegna segnali passivi o trasmette "in attesa". Non carica stili e non richiede wrappers esagerati, limitandosi ad usare l'oggetto integrato costruttore `fetch()`.
