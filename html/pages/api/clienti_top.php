<?php
// Evita output accidentale prima del JSON
ob_start();

session_start();
header('Content-Type: application/json; charset=utf-8');

// Verifica autenticazione
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_tipo'] !== 'manager') {
    ob_end_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Non autorizzato']);
    exit;
}

require_once '../../config/database.php';

try {
    $db = getDB();

    $stmt = $db->query("SELECT * FROM negozi.v_saldi_punti_300");
    $clienti = $stmt->fetchAll();

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'data' => $clienti,
        'count' => count($clienti)
    ]);

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
