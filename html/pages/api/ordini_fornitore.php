<?php
session_start();

header('Content-Type: application/json');

// Verifica se l'utente è loggato ed è un manager
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_tipo'] !== 'manager') {
    echo json_encode(['error' => 'Accesso non autorizzato']);
    exit;
}

require_once '../../config/database.php';

$piva = trim($_GET['piva'] ?? '');

if (empty($piva)) {
    echo json_encode(['error' => 'P.IVA non specificata']);
    exit;
}

try {
    $db = getDB();

    $stmt = $db->query("SELECT o.id_ordine, o.quantita, o.data_ordine, o.stato_ordine,
                               p.nome_prodotto, n.nome_negozio
                        FROM negozi.ordini_fornitori o
                        LEFT JOIN negozi.prodotti p ON o.prodotto = p.id_prodotto
                        LEFT JOIN negozi.negozi n ON o.negozio = n.id_negozio
                        WHERE o.fornitore = ?
                        ORDER BY o.data_ordine DESC", [$piva]);

    $ordini = $stmt->fetchAll();

    echo json_encode(['ordini' => $ordini]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Errore nel caricamento: ' . $e->getMessage()]);
}