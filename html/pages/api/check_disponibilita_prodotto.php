<?php
session_start();

header('Content-Type: application/json');

// Verifica se l'utente è loggato ed è un manager
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_tipo'] !== 'manager') {
    echo json_encode(['error' => 'Accesso non autorizzato']);
    exit;
}

require_once '../../config/database.php';

$id_prodotto = (int)($_GET['id_prodotto'] ?? 0);

if ($id_prodotto <= 0) {
    echo json_encode(['error' => 'ID prodotto non valido']);
    exit;
}

try {
    $db = getDB();

    // Verifica se almeno un fornitore attivo ha questo prodotto con quantità > 0
    $stmt = $db->query("SELECT COUNT(*) as disponibili
                        FROM negozi.magazzino_fornitore mf
                        JOIN negozi.fornitori f ON mf.piva_fornitore = f.piva
                        WHERE mf.prodotto = ?
                          AND mf.quantita > 0
                          AND f.attivo = true", [$id_prodotto]);
    $result = $stmt->fetch();

    $disponibile = ($result['disponibili'] ?? 0) > 0;

    echo json_encode([
        'disponibile' => $disponibile,
        'id_prodotto' => $id_prodotto
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Errore nel controllo: ' . $e->getMessage()]);
}
