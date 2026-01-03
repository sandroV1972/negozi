<?php
session_start();

// Verifica se l'utente è loggato ed è un manager
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_tipo'] !== 'manager') {
    header('Location: ../index.php?error=access_denied');
    exit;
}

require_once '../config/database.php';

$error = '';
$success = '';
$tessere = [];
$negozio = null;
$clienti_senza_tessera = [];

// Recupera l'ID del negozio dalla query string
$id_negozio = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_negozio <= 0) {
    $error = 'ID negozio non valido.';
} else {
    try {
        $db = getDB();

        // Gestione creazione nuova tessera
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crea_tessera') {
            $id_cliente = (int)$_POST['id_cliente'];

            if ($id_cliente > 0) {
                // Chiama la funzione per creare una nuova tessera per il cliente
                $stmt = $db->query("SELECT negozi.crea_tessera_cliente(?, ?) AS id_tessera", [$id_cliente, $id_negozio]);
                $nuova_tessera = $stmt->fetch();
                $id_tessera = $nuova_tessera['id_tessera'];
                // la funzione ritorna NULL in caso di errore
                if (!$id_tessera) {
                    throw new Exception('Impossibile creare la tessera per il cliente selezionato.');
                }
                $success = 'Tessera creata con successo!';
            }
        }

        // Recupera info negozio
        $stmt = $db->query("SELECT id_negozio, nome_negozio, indirizzo FROM negozi.negozi WHERE id_negozio = ?", [$id_negozio]);
        $negozio = $stmt->fetch();

        if (!$negozio) {
            $error = 'Negozio non trovato.';
        } else {
            // Recupera tessere del negozio usando la funzione
            $stmt = $db->query("SELECT * FROM negozi.tessere_negozio(?)", [$id_negozio]);
            $tessere = $stmt->fetchAll();

            // Recupera clienti senza tessera
            $stmt = $db->query("SELECT id_cliente, nome, cognome, cf FROM negozi.clienti WHERE tessera IS NULL ORDER BY cognome, nome");
            $clienti_senza_tessera = $stmt->fetchAll();
        }
    } catch (Exception $e) {
        $error = 'Errore: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tessere Negozio - Sistema Negozi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-warning">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard_manager.php">
               Dashboard Manager
            </a>

            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i>
                        <?= htmlspecialchars($_SESSION['user_nome']) ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="cambia_password.php">
                            <i class="bi bi-key"></i> Cambia password
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard_manager.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="gestione_negozi.php">Gestione Negozi</a></li>
                <li class="breadcrumb-item active">Tessere Negozio</li>
            </ol>
        </nav>

        <?php if (!empty($error)): ?>
        <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($negozio): ?>

        <!-- Header -->
        <div class="row mb-4">
            <div class="col">
                <h2>Tessere del Negozio</h2>
                <p class="text-muted">
                    <strong><?= htmlspecialchars($negozio['nome_negozio']) ?></strong> -
                    <?= htmlspecialchars($negozio['indirizzo']) ?>
                </p>
            </div>
            <div class="col-auto text-end">
                <?php if (!empty($clienti_senza_tessera)): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuovaTessera">
                    <i class="bi bi-person-plus"></i> Nuova Tessera
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Lista Tessere -->
        <?php if (isset($_GET['action']) && $_GET['action'] === 'new'): ?>

        <?php else: ?>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    Tessere Emesse (<?= count($tessere) ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($tessere)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                        <p class="mt-3 text-muted">Nessuna tessera emessa da questo negozio.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID Tessera</th>
                                    <th>Cliente</th>
                                    <th>Data Richiesta</th>
                                    <th>Saldo Punti</th>
                                    <th>Stato</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tessere as $tessera): ?>
                                    <tr>
                                        <td><strong>#<?= htmlspecialchars($tessera['numero_tessera']) ?></strong></td>
                                        <td>
                                            <?php if (isset($tessera['nome']) && isset($tessera['cognome'])): ?>
                                                <?= htmlspecialchars($tessera['nome'] . ' ' . $tessera['cognome']) ?>
                                            <?php elseif (isset($tessera['id_cliente'])): ?>
                                                Cliente #<?= htmlspecialchars($tessera['id_cliente']) ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (isset($tessera['data_richiesta'])): ?>
                                                <?= date('d/m/Y', strtotime($tessera['data_richiesta'])) ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-success"><?= htmlspecialchars($tessera['saldo_punti'] ?? 0) ?> punti</span>
                                        </td>
                                        <td>
                                            <?php if (isset($tessera['archiviata']) && $tessera['archiviata']): ?>
                                                <span class="badge bg-secondary">Archiviata</span>
                                            <?php else: ?>
                                                <span class="badge bg-primary">Attiva</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <!-- Modal Nuova Tessera -->
    <?php if (!empty($clienti_senza_tessera)): ?>
    <div class="modal fade" id="modalNuovaTessera" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-credit-card"></i> Nuova Tessera</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="crea_tessera">
                        <div class="mb-3">
                            <label for="id_cliente" class="form-label">Seleziona Cliente</label>
                            <select class="form-select" id="id_cliente" name="id_cliente" required>
                                <option value="">-- Seleziona un cliente --</option>
                                <?php foreach ($clienti_senza_tessera as $cliente): ?>
                                    <option value="<?= $cliente['id_cliente'] ?>">
                                        <?= htmlspecialchars($cliente['cognome'] . ' ' . $cliente['nome']) ?>
                                        (<?= htmlspecialchars($cliente['cf']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Solo i clienti senza tessera sono mostrati in questa lista.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-plus-circle"></i> Crea Tessera
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
