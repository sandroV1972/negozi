<?php
session_start();

// Verifica autenticazione
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_tipo'] !== 'manager') {
    http_response_code(403);
    echo '<div class="alert alert-danger">Non autorizzato</div>';
    exit;
}

require_once '../../config/database.php';

$id_negozio = isset($_GET['id_negozio']) ? (int)$_GET['id_negozio'] : 0;

// Gestione POST per aggiornare stato ordine
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id_ordine = (int)($_POST['id_ordine'] ?? 0);
    $action = $_POST['action'];

    if ($id_ordine > 0 && in_array($action, ['consegnato', 'annullato'])) {
        try {
            $db = getDB();
            //Aggiorna stato ordine
            if ($action === 'consegnato') {
                $db->query("UPDATE negozi.ordini_fornitori SET stato_ordine = 'consegnato' WHERE id_ordine = ?", [$id_ordine]);
            } elseif ($action === 'annullato') {
                $db->query("UPDATE negozi.ordini_fornitori SET stato_ordine = 'annullato' WHERE id_ordine = ?", [$id_ordine]);
            }

            echo '<div class="alert alert-success">Stato ordine aggiornato!</div>';
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">Errore: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
    exit;
}

if ($id_negozio <= 0) {
    echo '<div class="alert alert-warning">ID negozio non valido</div>';
    exit;
}

try {
    $db = getDB();

    $stmt = $db->query("SELECT o.id_ordine, o.prodotto, p.nome_prodotto, o.fornitore, f.nome_fornitore,
                               o.quantita, o.data_ordine, o.data_consegna, o.stato_ordine
                        FROM negozi.ordini_fornitori o
                        JOIN negozi.prodotti p ON o.prodotto = p.id_prodotto
                        JOIN negozi.fornitori f ON o.fornitore = f.piva
                        WHERE o.negozio = ? AND o.stato_ordine = 'emesso'
                        ORDER BY o.data_ordine DESC", [$id_negozio]);
    $ordini = $stmt->fetchAll();

    if (empty($ordini)) {
        echo '<div class="text-center py-4">
                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                <p class="mt-3 text-muted">Nessun ordine in attesa per questo negozio.</p>
              </div>';
        exit;
    }

    // Genera tabella HTML
    echo '<div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Prodotto</th>
                        <th>Fornitore</th>
                        <th>Qtà</th>
                        <th>Data Ordine</th>
                        <th>Consegna Prevista</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>';

    foreach ($ordini as $ordine) {
        $data_ordine = date('d/m/Y', strtotime($ordine['data_ordine']));
        $data_consegna = date('d/m/Y', strtotime($ordine['data_consegna']));

        echo '<tr>
                <td>#' . htmlspecialchars($ordine['id_ordine']) . '</td>
                <td>' . htmlspecialchars($ordine['nome_prodotto']) . '</td>
                <td>' . htmlspecialchars($ordine['nome_fornitore']) . '</td>
                <td>' . htmlspecialchars($ordine['quantita']) . '</td>
                <td>' . $data_ordine . '</td>
                <td>' . $data_consegna . '</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-success" onclick="aggiornaStatoOrdine(' . $ordine['id_ordine'] . ', \'consegnato\')" title="Segna come arrivato">
                            <i class="bi bi-check-circle"></i>
                        </button>
                        <button class="btn btn-danger" onclick="aggiornaStatoOrdine(' . $ordine['id_ordine'] . ', \'annullato\')" title="Annulla ordine">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                </td>
              </tr>';
    }

    echo '</tbody></table></div>';

} catch (Exception $e) {
    echo '<div class="alert alert-danger">Errore nel caricamento: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
