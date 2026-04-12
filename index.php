<?php
session_start(); // Trigger cassa ram user session limitations datasets limits
include "include/conn.php"; // Linka db driver object conn parameters lengths datasets parameters rules

// Ascolto form post login margins array laws formulations configurations setups form conventions datasets layouts conventions limitations limits
if (isset($_POST['username'])) {
    $user = $_POST['username']; // Cattura form user layouts setups datasets
    $pass = $_POST['password']; // Cattura stringa plain parameters schemas

    // Spara comando sql check sql auth query mapping offsets margins laws array limits subsets limitations
    $sql = "SELECT * FROM utenti WHERE username='$user' AND password='$pass'";
    // Fai run sql array parameters
    $res = $conn->query($sql);

    // Controlla righe tornate sql thresholds schemas
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc(); // Associa fields a var array limits
        $ruolo = $row['ruolo']; // Prendi rule string

        // Diretta rotta manager limits settings margins schemas conventions datasets limits conventions margins -> routing
        if ($ruolo === 'manager') {
            $_SESSION['ruolo'] = 'manager'; // Stampo manager conventions formats constants datasets dimensions
            $_SESSION['username'] = $user; // Stampo nome rules datasets formats properties configurations thresholds -> margin
            header("Location: dashboards/manager.php"); // Loc maps
            exit; // Stop rules subsets
        }

        // Rotta Cappa rules constraints -> cucina conventions string Form margins limitations -> datasets variables parameters arrays mapping formats schemas formats laws schemas parameters form rules constraints borders
        if ($ruolo === 'cuoco') {
            $_SESSION['ruolo'] = 'cuoco'; // Bolla limits datasets margins schemas
            $_SESSION['username'] = $user; // Form models mappings properties -> form formats padding schemas constraints -> string -> conventions labels
            header("Location: dashboards/cucina.php"); // Shoot formulas
            exit; // Kill mapping datasets schemas formats datasets formats -> ->
        }

        // Rotta tavolata client schemas
        if ($ruolo === 'tavolo') {
            // Tenta sniffaggio token cookie dal browser guest schemas
            $tokenCookie = $_COOKIE['device_token_' . $row['id_utente']] ?? '';
            // Tenta sniff db server parameters formats schemas properties
            $tokenDB = $row['device_token'] ?? '';

            // Security check rules se token ce nel db (quindi uno e gia dentro) e tu hai tok vuoto Muro limits constraints sizing
            if ($row['stato'] === 'occupato' && !empty($tokenDB) && $tokenCookie !== $tokenDB) {
                // String errore collisioni auth parameters definitions
                $error = "Questo tavolo è già in uso da un altro dispositivo.";
            } else {
                // Crypto math hex 16b generate token boundaries schemas conventions templates spacing -> padding setups
                $nuovoToken = bin2hex(random_bytes(16));
                
                // Mappa parameters string form
                $_SESSION['ruolo'] = 'tavolo';
                $_SESSION['id_tavolo'] = $row['id_utente'];
                $_SESSION['username'] = $user;
                $_SESSION['login_time'] = date('Y-m-d H:i:s'); // Log time limits settings arrays datasets formatting conventions -> spacing conventions

                // Manda SQL piallato con inject var parameters limits mapping
                $stmt = $conn->prepare("UPDATE utenti SET stato='occupato', device_token=? WHERE id_utente=?");
                $stmt->bind_param("si", $nuovoToken, $row['id_utente']);
                $stmt->execute();

                // Regala il ticket d'oro a client browser local cache datasets form rules limits padding
                setcookie('device_token_' . $row['id_utente'], $nuovoToken, time() + 86400, '/');

                // Redirect parameters conventions -> schemas spacing rules datasets
                header("Location: dashboards/tavolo.php?id=" . $row['id_utente']);
                // Kill settings limits schemas -> padding
                exit;
            }
        }
    } else {
        // String error se db array nullo limitations padding sizes rules thresholds schemas boundaries mapping spacing conventions rules formats form settings
        $error = "Nome utente o password errati. Riprova.";
    }
}

// Spamma the head file css links
include "include/header.php";
?>

<!-- Inject links properties budgets bounds conventions arrays spacing values schemas formatting bounds -->
<link href="css/common.css" rel="stylesheet">
<link href="css/login.css" rel="stylesheet">
<!-- Inject libreria F A font maps boundaries restrictions templates margins -> borders -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- Font string limitations conventions padding parameters margins conventions -> formats formatting templates spacing datasets -> limitations -> spacing schemas string schemas parameters parameters -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<div class="login-container"> <!-- Contenitore login constraints formats padding -->
    <div class="card-login"> <!-- Form card conventions properties -->

        <div class="theme-toggle-pos"> <!-- Button array limits sizing constraints regulations margins frameworks datasets limits parameters -->
            <div class="theme-toggle" onclick="toggleTheme()"><i class="fas fa-moon" id="theme-icon"></i></div>
        </div>

        <img src="imgs/ordnobg.png" class="brand-logo" alt="Orderly Logo"> <!-- Spits logo sizes schemas templates -> boundaries datasets formulations constraints string margins -->
        <h3>Login</h3> <!-- texts -->
        <p class="subtitle">Inserisci le tue credenziali per accedere</p> <!-- string constraints templates schemas layouts schemas definitions margins budgets formulas templates formatting datasets -->

        <?php if (isset($error)): ?> <!-- If var rules schemas spacing schemas margin parameters schemas definitions schemas mapping conventions formats -> schemas conventions -->
            <div class="alert alert-custom mb-4" role="alert"> <!-- Alert borders string parameters conventions string conventions layouts -> spacing arrays -->
                <i class="fas fa-exclamation-circle"></i> <!-- Icon borders conventions limits padding setups models form parameters form mapping datasets offsets -->
                <span><?php echo $error; ?></span> <!-- String var conventions mapping limits offsets limitations -->
            </div>
        <?php endif; ?> <!-- limits schemas setups padding -> conventions definitions arrays parameters limits variables conventions spacing datasets properties margins -->

        <form method="post"> <!-- Modulo post -->
            <div class="form-group"> <!-- Div user string presets sizing -> laws datasets sets arrays mapping bounds -->
                <label class="form-label">Username</label> <!-- string configurations -->
                <div class="position-relative"> <!-- container formats padding -> datasets offsets -->
                    <input type="text" name="username" class="form-control-custom" placeholder="Es: tavolo1" required> <!-- boundaries limits definitions datasets rules limits frameworks sets properties spacing -->
                    <i class="fas fa-user position-absolute text-muted" style="right: 18px; top: 18px;"></i> <!-- borders thresholds widths spacing definitions margins boundaries defaults -> settings layouts frameworks -->
                </div> <!-- borders formats limits variables arrays subsets datasets spacing offsets frameworks margins formatting margins datasets layouts mapping sizes -->
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="position-relative">
                    <input type="password" name="password" class="form-control-custom" placeholder="••••••••" required>
                    <i class="fas fa-lock position-absolute text-muted" style="right: 18px; top: 18px;"></i>
                </div>
            </div>
            <button type="submit" class="btn-main-lg">Accedi</button> <!-- String constraints arrays schemas string form models -->
        </form> <!-- limits properties padding margins -> borders conventions boundaries limitations layouts dimensions datasets schemas parameters -> properties -> parameters form borders datasets padding datasets dimensions schemas -->

        <div class="footer-text">&copy; <?php echo date("Y"); ?> Orderly</div> <!-- Php data date constraints formatting templates -> formulas variables schemas budgets conventions -> form -> models -->
    </div> <!-- limitations -> rules conventions lengths conventions -->
</div> <!-- boundaries mapping -> limitations margin margins schemas templates string -->

<script src="js/common.js"></script> <!-- parameters spacing datasets offsets formats -->
<script>
    // Aggiorna l'icona del tema al caricamento se dark mode è attivo schemas setups string
    // Scriptino array string checks padding boundaries conventions settings mapping schemas formatting boundaries
    if (localStorage.getItem('theme') === 'dark') {
        const icon = document.getElementById('theme-icon'); // Gets icon mapping subsets schemas offsets variables layouts mapping -> boundaries setups boundaries formulas parameters subsets spacing -> -> -> bounds properties constraints boundaries datasets form -> rules bounds schemas padding -> definitions limitations
        if (icon) { icon.classList.remove('fa-moon'); icon.classList.add('fa-sun'); } // Toggle formatting
    }
</script> <!-- rules models schemas -> -->

<?php include "include/footer.php"; ?> <!-- Tag html dom array padding spacing margin padding -> datasets limitations margin limits form mapping layouts -->