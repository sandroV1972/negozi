<?php
session_start();

// Verifica autenticazione
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_tipo'] !== 'manager') {
    http_response_code(403);
    echo json_encode(['error' => 'Non autorizzato']);
    exit;
}

header('Content-Type: application/json');

require_once '../../config/database.php';

try {
    $db = getDB();

    // TODO: Implementa la query per recuperare i clienti con 300+ punti
    // Esempio di query (da adattare al tuo schema):
     $stmt = $db->query("SELECT *
                        FROM negozi.saldi_punti_300");
    $clienti = $stmt->fetchAll();

    // Placeholder: restituisce array vuoto
    $clienti = [];

    echo json_encode([
        'success' => true,
        'data' => $clienti,
        'count' => count($clienti)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
