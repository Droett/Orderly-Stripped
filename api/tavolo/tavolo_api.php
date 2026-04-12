<?php
session_start();
require_once '../../include/conn.php';
require_once '../../include/auth/check_permesso.php';

$action = $_GET['action'] ?? '';
if (!verificaPermesso($conn, "tavolo/" . $action)) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Non autorizzato', 'message' => 'Non autorizzato']));
}

switch ($action) {
    case 'aggiungi_al_carrello': {
        // Aggiunge un piatto al carrello (o incrementa la quantità se già presente)
        
                header('Content-Type: application/json');
        
        $idTavolo = $_SESSION['id_tavolo'];
        $idPiatto = intval($_POST['id_alimento'] ?? 0);
        $qta = intval($_POST['quantita'] ?? 1);
        
        if ($idPiatto <= 0) {
            echo json_encode(['success' => false, 'message' => 'Piatto non valido.']);
            exit;
        }
        
        // Cerca un carrello aperto (ordine in_attesa) o ne crea uno nuovo
        $res = $conn->query("SELECT id_ordine FROM ordini WHERE id_utente = $idTavolo AND stato = 'in_attesa' LIMIT 1");
        
        if ($res->num_rows > 0) {
            $idOrdine = $res->fetch_assoc()['id_ordine'];
        } else {
            $conn->query("INSERT INTO ordini (id_utente, stato, data_ora) VALUES ($idTavolo, 'in_attesa', NOW())");
            $idOrdine = $conn->insert_id;
        }
        
        // Se il piatto è già nel carrello, somma la quantità; altrimenti inserisce una nuova riga
        $check = $conn->query("SELECT quantita FROM dettaglio_ordini WHERE id_ordine = $idOrdine AND id_alimento = $idPiatto");
        
        if ($check->num_rows > 0) {
            $conn->query("UPDATE dettaglio_ordini SET quantita = quantita + $qta WHERE id_ordine = $idOrdine AND id_alimento = $idPiatto");
        } else {
            $conn->query("INSERT INTO dettaglio_ordini (id_ordine, id_alimento, quantita) VALUES ($idOrdine, $idPiatto, $qta)");
        }
        
        echo json_encode(['success' => true, 'message' => 'Aggiunto al carrello.']);
        
        break;
    }

    case 'get_carrello': {
        // Recupera il contenuto del carrello (ordine in bozza)
        
                header('Content-Type: application/json');
        
        if (!isset($_SESSION['id_tavolo'])) {
            echo json_encode([]);
            exit;
        }
        
        $idTavolo = intval($_SESSION['id_tavolo']);
        
        $result = $conn->query("SELECT d.id_alimento, d.quantita, a.nome_piatto, a.prezzo
            FROM dettaglio_ordini d
            JOIN ordini o ON d.id_ordine = o.id_ordine
            JOIN alimenti a ON d.id_alimento = a.id_alimento
            WHERE o.id_utente = $idTavolo AND o.stato = 'in_attesa'");
        
        echo json_encode($result ? $result->fetch_all(MYSQLI_ASSOC) : []);
        
        break;
    }

    case 'invia_ordine': {
        // Invia l'ordine in cucina (converte il carrello in ordine ufficiale)
        
                header('Content-Type: application/json');
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['prodotti'])) {
            echo json_encode(['success' => false, 'message' => 'Il carrello è vuoto.']);
            exit;
        }
        
        $idTavolo = $_SESSION['id_tavolo'];
        
        $conn->begin_transaction();
        
        try {
            // Crea la testata dell'ordine
            $stmt = $conn->prepare("INSERT INTO ordini (id_utente, stato, data_ora) VALUES (?, 'in_attesa', NOW())");
            $stmt->bind_param("i", $idTavolo);
            if (!$stmt->execute())
                throw new Exception("Errore creazione ordine.");
        
            $idOrdine = $conn->insert_id;
        
            // Inserisce i singoli piatti
            $det = $conn->prepare("INSERT INTO dettaglio_ordini (id_ordine, id_alimento, quantita, note) VALUES (?, ?, ?, ?)");
        
            foreach ($data['prodotti'] as $p) {
                if ($p['qta'] > 0) {
                    $note = $p['note'] ?? null;
                    $det->bind_param("iiis", $idOrdine, $p['id'], $p['qta'], $note);
                    if (!$det->execute())
                        throw new Exception("Errore inserimento piatto.");
                }
            }
        
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Ordine inviato in cucina.']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        
        break;
    }

    case 'leggi_ordini_tavolo': {
        // Restituisce lo storico ordini del tavolo per la sessione corrente
        
                header('Content-Type: application/json');
        
        $idTavolo = $_SESSION['id_tavolo'];
        
        // Recupera l'inizio della sessione per filtrare solo gli ordini attuali
        $stmtSess = $conn->prepare("SELECT sessione_inizio FROM utenti WHERE id_utente = ?");
        $stmtSess->bind_param("i", $idTavolo);
        $stmtSess->execute();
        $resSess = $stmtSess->get_result()->fetch_assoc();
        $orarioLogin = $resSess['sessione_inizio'] ?? '1970-01-01 00:00:00';
        
        $stmt = $conn->prepare("SELECT o.id_ordine, o.stato, o.data_ora, d.quantita, a.nome_piatto, a.prezzo, d.note
            FROM ordini o
            JOIN dettaglio_ordini d ON o.id_ordine = d.id_ordine
            JOIN alimenti a ON d.id_alimento = a.id_alimento
            WHERE o.id_utente = ? AND o.data_ora >= ?
            ORDER BY o.data_ora DESC");
        
        $stmt->bind_param("is", $idTavolo, $orarioLogin);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $ordini = [];
        
        // Raggruppa i piatti per ordine
        while ($row = $result->fetch_assoc()) {
            $id = $row['id_ordine'];
            if (!isset($ordini[$id])) {
                $ordini[$id] = [
                    'id_ordine' => $id,
                    'stato' => $row['stato'],
                    'ora' => date('H:i', strtotime($row['data_ora'])),
                    'data' => date('d/m/Y', strtotime($row['data_ora'])),
                    'piatti' => [],
                    'totale' => 0
                ];
            }
            $ordini[$id]['piatti'][] = [
                'nome' => $row['nome_piatto'],
                'qta' => $row['quantita'],
                'prezzo' => number_format($row['prezzo'], 2),
                'note' => $row['note'] ?? ''
            ];
            $ordini[$id]['totale'] += $row['quantita'] * $row['prezzo'];
        }
        
        foreach ($ordini as &$o)
            $o['totale'] = number_format($o['totale'], 2);
        
        echo json_encode(array_values($ordini));
        
        break;
    }

    case 'rimuovi_dal_carrello': {
        // Decrementa la quantità di un piatto nel carrello (o lo rimuove se arriva a 0)
        
                header('Content-Type: application/json');
        
        if (!isset($_SESSION['id_tavolo'])) {
            echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
            exit;
        }
        
        $idTavolo = intval($_SESSION['id_tavolo']);
        $idPiatto = intval($_POST['id_alimento'] ?? 0);
        
        if ($idPiatto <= 0) {
            echo json_encode(['success' => false, 'message' => 'Piatto non valido.']);
            exit;
        }
        
        $res = $conn->query("SELECT id_ordine FROM ordini WHERE id_utente = $idTavolo AND stato = 'in_attesa' LIMIT 1");
        
        if ($res->num_rows > 0) {
            $idOrdine = $res->fetch_assoc()['id_ordine'];
        
            $qRes = $conn->query("SELECT quantita FROM dettaglio_ordini WHERE id_ordine = $idOrdine AND id_alimento = $idPiatto");
        
            if ($qRes->num_rows > 0) {
                $qta = $qRes->fetch_assoc()['quantita'];
        
                // Se qta > 1 decrementa, altrimenti elimina la riga
                $sql = ($qta > 1)
                    ? "UPDATE dettaglio_ordini SET quantita = quantita - 1 WHERE id_ordine = $idOrdine AND id_alimento = $idPiatto"
                    : "DELETE FROM dettaglio_ordini WHERE id_ordine = $idOrdine AND id_alimento = $idPiatto";
        
                echo json_encode($conn->query($sql) ? ['success' => true] : ['success' => false, 'message' => $conn->error]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Piatto non nel carrello.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Nessun carrello attivo.']);
        }
        
        break;
    }

    case 'verifica_sessione': {
        // Verifica periodicamente che la sessione del tavolo sia ancora valida
        // (polling ogni 5s da tavolo.js)
        
                header('Content-Type: application/json');
        
        $idTavolo = intval($_SESSION['id_tavolo']);
        $tokenCookie = $_COOKIE['device_token_' . $idTavolo] ?? '';
        
        $stmt = $conn->prepare("SELECT stato, device_token FROM utenti WHERE id_utente = ?");
        $stmt->bind_param("i", $idTavolo);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        
        // Sessione valida solo se: tavolo occupato, token presente e corrispondente
        $valida = $row && $row['stato'] === 'occupato' && !empty($row['device_token']) && $row['device_token'] === $tokenCookie;
        
        echo json_encode(['valida' => $valida]);
        
        break;
    }

    default:
        die('Azione non valida.');
}
