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

    $stmt = $db->query("SELECT * FROM negozi.lista_ordini_fornitore(?)", [$piva]);
    $ordini = $stmt->fetchAll();

    echo json_encode(['ordini' => $ordini]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Errore nel caricamento: ' . $e->getMessage()]);
}