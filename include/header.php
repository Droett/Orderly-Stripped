<!DOCTYPE html> 
<html lang="it"> 

<head> 
  <!-- Codifica della pagina per supportare accenti/caratteri speciali -->
  <meta charset="UTF-8"> 
  <!-- Metatag essenziale per rendere il sito Responsive Design su smartphone/tablet -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
  <!-- Inclusione via CDN del framework core Bootstrap CSS v5.3 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"> 
  <title>Orderly</title> 
</head> 

<body> 
  <script> 
    // ---- BLOCCO JS AGGANCIATO ALL'HEADER ----
    // Questo script in testa gestisce le preferenze "Chiaro/Scuro" prima che la UI della pagina renderizzi, evitando ricaricamenti.

    // Funzione invocata ogni qual volta un utente preme un pulsante (icona Luna/Sole) nella UI
    function toggleTheme() {
      // Verifica se in questo momento l'attributo speciale data-theme sul body è settato a 'dark'
      const isDark = document.body.getAttribute('data-theme') === 'dark';

      // Imposta il prossimo tema invertendo la logica (se era chiaro, scuro; se era scuro, chiaro)
      const newTheme = isDark ? 'light' : 'dark';

      // Applica la nuova stringa di tema al Body element, triggerando di fatto al volo l'esecuzione dei CSS personalizzati dark/light
      document.body.setAttribute('data-theme', newTheme);

      // Cerca tutti gli elementi icona theme changer (di norma in mobile o pc potresti averne più di uno)
      document.querySelectorAll('[id="theme-icon"]').forEach(icon => {
        // Scambia in tempo reale le icone CSS FontAwesome 
        icon.classList.replace(isDark ? 'fa-sun' : 'fa-moon', isDark ? 'fa-moon' : 'fa-sun');
      });

      // Mettiamo in cache permanente del Browser dell'utente il tema scelto tramite localStorage, al prossimo refresh non la perderà.
      localStorage.setItem('theme', newTheme);
    }

    // Questa è una Immediately Invoked Function Expression (IIFE)
    // È essenziale: viene eseguita "A Cannone" (Appena creata) prima di leggere il resto della pagina bloccando il parser.
    (function () {
      // Va a spiarsi la memoria fissa del browser dell'utente (localStorage) per vedere se c'è "dark" memorizzato
      if (localStorage.getItem('theme') === 'dark') {
         // Applica il dark theme subitissimo al Body così la pagina non "lampiggierà" bianca mentre si costruisce DOM elements.
        document.body.setAttribute('data-theme', 'dark');
      }
    })(); 
  </script>
