<!DOCTYPE html> <!-- Intestazione testata tecnica al web browser certificante modulo html 5 standard rules -->
<html lang="it"> <!-- Coperchio doc html linguistico ita -->

<head> <!-- Cabina di pre configurazione head file css cdn meta e info testate browser invisible ai rendering base -->
  <meta charset="UTF-8"> <!-- Forza mappa ASCII testo europeo per render e parsing accenti layout html -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Blocco forzatura schermo mobile per ingrandimenti standard CSS a schermo pieno constraints array limits layout rendering parameters bounds schemas properties limits boundaries defaults schemas offsets strings formati -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"> <!-- Cablaggio di rete al file web remoto CDN contenente tutte le regole Boot Strap web framework layout CSS templates setups formats margins rules configurations presets datasets configurations borders subsets constraints formats boundaries definitions margins mapping layouts rules templates conventions schemas margins mapping defaults sets array limitations setups limitations regulations rules dimensions formulations margins datasets formatting limitations margins configurations spacing limits sets conventions variables sizing standards definitions formulas -->
  <title>Orderly</title> <!-- Striscina appesa sulla schedina del broswer nome dell'app string schemas -->
</head> <!-- Sepolta testa limits -->

<body> <!-- Core corpo fisico visibile html ui layout limits models -->
  <script> // Piccolo inchiostro js scriptino rules boundaries
    // Tasto cambia tema switcher function action per logiche temi chiari e neri
    function toggleTheme() {
      // Chiedi true / false a tag root data se ha dark array setting rules subsets constraints
      const isDark = document.body.getAttribute('data-theme') === 'dark';
      
      // Algebra set reverse light altrimenti pittura in dark layouts mappings templates limits boundaries
      const newTheme = isDark ? 'light' : 'dark';

      // Imposta nuova variabile a root HTML limits
      document.body.setAttribute('data-theme', newTheme);

      // Cercati tuti i pulsanti logo luna
      document.querySelectorAll('[id="theme-icon"]').forEach(icon => {
        // Scambia le icone fa awesome tra luna sole sole luna rules definitions models datasets setups array formulas
        icon.classList.replace(isDark ? 'fa-sun' : 'fa-moon', isDark ? 'fa-moon' : 'fa-sun');
      });

      // Appendi biscotto cookie persistente al dispositivo locale localstorage per memorizzare la vista x i prossimi riavvii frameworks sizes configurations bounds
      localStorage.setItem('theme', newTheme);
    }

    // Funzione auto iniettata (IIFE) che parte prima di costruire il body a cannone evitando lampi bianchi sui riavvii dark mode
    (function () {
      // Fai sondaggio sulla memoria browser rom persistente
      if (localStorage.getItem('theme') === 'dark') {
        // Incide in anticipo bruciando i tempi lo scuro al root body theme parameters offsets mappings standards schemas datasets dimensions boundaries configurations regulations limitations properties formatting limits models schemas margin boundaries limits formatting sizes conventions conventions boundaries frameworks setups borders budgets limits layouts conventions configurations layouts formatting formulas offsets
        document.body.setAttribute('data-theme', 'dark');
      }
    })(); // fine iife string mappings boundaries array settings formatting setups boundaries budgets formations templates formats models setups formats conventions datasets schemas
  </script>