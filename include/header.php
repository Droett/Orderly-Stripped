<!DOCTYPE html>
<html lang="it">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <title>Orderly</title>
</head>

<body>
  <script>
    // Cambio tema chiaro/scuro (disponibile globalmente)
    function toggleTheme() {
      const isDark = document.body.getAttribute('data-theme') === 'dark';
      const newTheme = isDark ? 'light' : 'dark';

      document.body.setAttribute('data-theme', newTheme);

      document.querySelectorAll('[id="theme-icon"]').forEach(icon => {
        icon.classList.replace(isDark ? 'fa-sun' : 'fa-moon', isDark ? 'fa-moon' : 'fa-sun');
      });

      localStorage.setItem('theme', newTheme);
    }

    // Applica tema salvato immediatamente (evita flash bianco)
    (function () {
      if (localStorage.getItem('theme') === 'dark') {
        document.body.setAttribute('data-theme', 'dark');
      }
    })();
  </script>