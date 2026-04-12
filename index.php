<?php
session_start();
include "include/conn.php";

// Login
if (isset($_POST['username'])) {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    $sql = "SELECT * FROM utenti WHERE username='$user' AND password='$pass'";
    $res = $conn->query($sql);

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $ruolo = $row['ruolo'];

        // Manager
        if ($ruolo === 'manager') {
            $_SESSION['ruolo'] = 'manager';
            $_SESSION['username'] = $user;
            header("Location: dashboards/manager.php");
            exit;
        }

        // Cuoco
        if ($ruolo === 'cuoco') {
            $_SESSION['ruolo'] = 'cuoco';
            $_SESSION['username'] = $user;
            header("Location: dashboards/cucina.php");
            exit;
        }

        // Tavolo
        if ($ruolo === 'tavolo') {
            $tokenCookie = $_COOKIE['device_token_' . $row['id_utente']] ?? '';
            $tokenDB = $row['device_token'] ?? '';

            if ($row['stato'] === 'occupato' && !empty($tokenDB) && $tokenCookie !== $tokenDB) {
                $error = "Questo tavolo è già in uso da un altro dispositivo.";
            } else {
                $nuovoToken = bin2hex(random_bytes(16));
                $_SESSION['ruolo'] = 'tavolo';
                $_SESSION['id_tavolo'] = $row['id_utente'];
                $_SESSION['username'] = $user;
                $_SESSION['login_time'] = date('Y-m-d H:i:s');

                $stmt = $conn->prepare("UPDATE utenti SET stato='occupato', device_token=? WHERE id_utente=?");
                $stmt->bind_param("si", $nuovoToken, $row['id_utente']);
                $stmt->execute();

                setcookie('device_token_' . $row['id_utente'], $nuovoToken, time() + 86400, '/');

                header("Location: dashboards/tavolo.php?id=" . $row['id_utente']);
                exit;
            }
        }
    } else {
        $error = "Nome utente o password errati. Riprova.";
    }
}

include "include/header.php";
?>

<link href="css/common.css" rel="stylesheet">
<link href="css/login.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<div class="login-container">
    <div class="card-login">

        <div class="theme-toggle-pos">
            <div class="theme-toggle" onclick="toggleTheme()"><i class="fas fa-moon" id="theme-icon"></i></div>
        </div>

        <img src="imgs/ordnobg.png" class="brand-logo" alt="Orderly Logo">
        <h3>Login</h3>
        <p class="subtitle">Inserisci le tue credenziali per accedere</p>

        <?php if (isset($error)): ?>
            <div class="alert alert-custom mb-4" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label class="form-label">Username</label>
                <div class="position-relative">
                    <input type="text" name="username" class="form-control-custom" placeholder="Es: tavolo1" required>
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
            <button type="submit" class="btn-main-lg">Accedi</button>
        </form>

        <div class="footer-text">&copy; <?php echo date("Y"); ?> Orderly</div>
    </div>
</div>

<script src="js/common.js"></script>
<script>
    // Aggiorna l'icona del tema al caricamento se dark mode è attivo
    if (localStorage.getItem('theme') === 'dark') {
        const icon = document.getElementById('theme-icon');
        if (icon) { icon.classList.remove('fa-moon'); icon.classList.add('fa-sun'); }
    }
</script>

<?php include "include/footer.php"; ?>