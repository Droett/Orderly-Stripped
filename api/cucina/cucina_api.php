<?php
session_start();
require_once '../../include/conn.php';
require_once '../../include/auth/check_permesso.php';

$action = $_GET['action'] ?? '';
if (!verificaPermesso($conn, "cucina/" . $action)) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Non autorizzato', 'message' => 'Non autorizzato']));
}

switch ($action) {
    case 'cambia_stato_ordine': {
        // Cambia lo stato di un ordine (in_attesa -> in_preparazione -> pronto)
        
                header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $id_ordine = $input['id_ordine'] ?? null;
        $nuovo_stato = $input['nuovo_stato'] ?? null;
        
        $stati_validi = ['in_attesa', 'in_preparazione', 'pronto'];
        
        if (!$id_ordine || !in_array($nuovo_stato, $stati_validi)) {
            echo json_encode(['success' => false, 'message' => 'Dati non validi.']);
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE ordini SET stato = ? WHERE id_ordine = ?");
        $stmt->bind_param("si", $nuovo_stato, $id_ordine);
        
        echo json_encode($stmt->execute() ? ['success' => true] : ['success' => false, 'message' => 'Errore: ' . $conn->error]);
        
        break;
    }

    case 'leggi_ordini_cucina': {
        // Recupera tutti gli ordini non ancora completati per la kanban cucina
        
                header('Content-Type: application/json');
        
        $sql = "SELECT o.id_ordine, o.id_utente, t.username, o.stato, o.data_ora,
                    d.quantita, a.nome_piatto, d.note
                FROM ordini o
                LEFT JOIN utenti t ON o.id_utente = t.id_utente
                JOIN dettaglio_ordini d ON o.id_ordine = d.id_ordine
                JOIN alimenti a ON d.id_alimento = a.id_alimento
                WHERE o.stato IN ('in_attesa', 'in_preparazione')
                ORDER BY o.data_ora ASC";
        
        $res = $conn->query($sql);
        if (!$res) {
            echo json_encode(["error" => $conn->error]);
            exit;
        }
        
        $ordini = [];
        
        // Raggruppa i piatti per ordine
        while ($row = $res->fetch_assoc()) {
            $id = $row['id_ordine'];
            if (!isset($ordini[$id])) {
                $ordini[$id] = [
                    'id_ordine' => $id,
                    'tavolo' => !empty($row['username']) ? $row['username'] : "Tavolo " . $row['id_utente'],
                    'stato' => $row['stato'],
                    'ora' => date('H:i', strtotime($row['data_ora'])),
                    'piatti' => []
                ];
            }
            $ordini[$id]['piatti'][] = ['nome' => $row['nome_piatto'], 'qta' => $row['quantita'], 'note' => $row['note'] ?? ''];
        }
        
        echo json_encode(array_values($ordini));
        
        break;
    }

    default:
        die('Azione non valida.');
}
