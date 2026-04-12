<?php
// Verifica i permessi di accesso a un endpoint tramite la tabella permessi_endpoint

function verificaPermesso($conn, $endpoint) {
    if (!isset($_SESSION['ruolo'])) return false;

    $ruolo = $_SESSION['ruolo'];
    $stmt = $conn->prepare("SELECT 1 FROM permessi_endpoint WHERE endpoint = ? AND ruolo = ? LIMIT 1");
    $stmt->bind_param("ss", $endpoint, $ruolo);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->num_rows > 0;
}
?>
