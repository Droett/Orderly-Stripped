<?php
// Avvia la sessione per mantenere l'utente connesso
session_start();

// Include la connessione al database
include "include/conn.php";

// Controlla se il form di login è stato inviato tramire richiesta POST
if (isset($_POST['username'])) {
    // Acquisisce i dati dal form
    $user = $_POST['username'];
    $pass = $_POST['password'];

    // Cerca l'utente nel database con nome utente e password corrispondenti
    $sql = "SELECT * FROM utenti WHERE username='$user' AND password='$pass'";
    $res = $conn->query($sql);

    // Se esiste almeno un record che fa match
    if ($res->num_rows > 0) {
        // Estrai l'intera riga dell'utente dal db
        $row = $res->fetch_assoc();
        $ruolo = $row['ruolo'];

        // Salva in sessione le info base
        $_SESSION['ruolo'] = $ruolo;
        $_SESSION['username'] = $user;

        // Se l'utente è un manager, mandalo alla sua dashboard proprietaria
        if ($ruolo === 'manager') {
            header("Location: dashboards/manager.php");
            exit; // Ferma lo script qui
        }

        // Se l'utente è un cuoco, mandalo in cucina
        if ($ruolo === 'cuoco') {
            header("Location: dashboards/cucina.php");
            exit;
        }

        // Se chi effettua l'accesso è un Tavolo
        if ($ruolo === 'tavolo') {
            // Salva l'ID specifico del tavolo in sessione
            $_SESSION['id_tavolo'] = $row['id_utente'];
            // Registra quando il tavolo è stato "aperto"
            $_SESSION['login_time'] = date('Y-m-d H:i:s');

            // Imposta lo stato del tavolo a 'occupato' per mostrarlo rosso nella griglia del manager
            $stmt = $conn->prepare("UPDATE utenti SET stato='occupato' WHERE id_utente=?");
            $stmt->bind_param("i", $row['id_utente']);
            $stmt->execute();

            // Reindirizza al menu per ordinare
            header("Location: dashboards/tavolo.php?id=" . $row['id_utente']);
            exit;
        }
    } else {
        // Se i dati non corrispondono, prepara un msg d'errore
        $error = "Nome utente o password errati. Riprova.";
    }
}

// Inclusione della porzione HTML Head comune
include "include/header.php";
?>

<!-- Inclusione CSS specifici per il layout della pagina di login -->
<link href="css/common.css" rel="stylesheet">
<link href="css/login.css" rel="stylesheet">
<!-- Inclusione librerie icone e font Google -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<div class="login-container">
    <div class="card-login">

        <!-- Pulsante fluttuante in alto a destra per scambiare Tema Chiaro / Scuro -->
        <div class="theme-toggle-pos">
            <div class="theme-toggle" onclick="toggleTheme()"><i class="fas fa-moon" id="theme-icon"></i></div>
        </div>

        <!-- Logo dell'applicazione -->
        <img src="imgs/ordnobg.png" class="brand-logo" alt="Orderly Logo">
        <h3>Login</h3>
        <p class="subtitle">Inserisci le tue credenziali per accedere</p>

        <!-- Se c'è stato un errore (Password errata), stama un alert Box -->
        <?php if (isset($error)): ?>
            <div class="alert alert-custom mb-4" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <!-- Form effettivo rivolto a se stesso (Method=POST senza Action) -->
        <form method="post">
            <div class="form-group">
                <label class="form-label">Username</label>
                <div class="position-relative">
                    <input type="text" name="username" class="form-control-custom" placeholder="Es: tavolo1" required>
                    <!-- Icona decorativa inserita in modo assoluto a destra nell'input -->
                    <i class="fas fa-user position-absolute text-muted" style="right: 18px; top: 18px;"></i>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="position-relative">
                    <input type="password" name="password" class="form-control-custom" placeholder="••••••••" required>
                    <i class="fas fa-lock position-absolute text-muted" style="right: 18px; top: 18px;"></i>
                </div>
            </div>
            <!-- Bottone invio credenziali -->
            <button type="submit" class="btn-main-lg">Accedi</button>
        </form>

        <div class="footer-text">&copy; <?php echo date("Y"); ?> Orderly</div>
    </div>
</div>

<script src="js/common.js"></script>
<script>
    // Al caricamento, controlla quale tema ha salvato l'utente sulla sua memoria localStorage
    if (localStorage.getItem('theme') === 'dark') {
        const icon = document.getElementById('theme-icon');
        // Scambia attivamente l'icona da luna a sole se il dark mode è acceso
        if (icon) { icon.classList.remove('fa-moon'); icon.classList.add('fa-sun'); }
    }
</script>

<?php include "include/footer.php"; ?>
