<?php
session_start();

// Verifica se l'utente è loggato ed è un manager
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_tipo'] !== 'manager') {
    header('Location: ../index.php?error=access_denied');
    exit;
}

require_once '../config/database.php';

// Genera CSRF token se non esiste
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Recupera messaggio dalla sessione (dopo redirect)
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

$error = '';
$action = $_GET['action'] ?? '';

// Gestione azioni POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Verifica CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token non valido. Possibile attacco CSRF.');
    }

    $piva = trim($_POST['piva'] ?? '');
    $ragione_sociale = trim($_POST['ragione_sociale'] ?? '');
    $indirizzo = trim($_POST['indirizzo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');

    try {
        $db = getDB();

        // Aggiorna fornitore
        if ($_POST['action'] === 'update') {
            if (empty($piva) || empty($ragione_sociale)) {
                throw new RuntimeException('Partita IVA e Ragione Sociale sono obbligatori.');
            }

            // P.IVA originale viene dall'URL
            $piva_originale = trim($_GET['piva'] ?? '');
            if (empty($piva_originale)) {
                throw new RuntimeException('P.IVA fornitore non valida.');
            }

            $db->query("UPDATE negozi.fornitori
                        SET piva = ?, nome_fornitore = ?, indirizzo = NULLIF(?,''),
                            email = NULLIF(?,''), telefono = NULLIF(?,'')
                        WHERE piva = ?",
                        [$piva, $ragione_sociale, $indirizzo, $email, $telefono, $piva_originale]);

            $_SESSION['message'] = "Fornitore modificato correttamente.";
            header('Location: gestione_fornitori.php');
            exit;
        }

        // Crea nuovo fornitore
        if ($_POST['action'] === 'create') {
            if (empty($piva) || empty($ragione_sociale)) {
                throw new RuntimeException('Partita IVA e Ragione Sociale sono obbligatori.');
            }

            // Valida formato P.IVA (11 cifre)
            if (!preg_match('/^\d{11}$/', $piva)) {
                throw new RuntimeException('La Partita IVA deve essere di 11 cifre numeriche.');
            }

            $db->query("INSERT INTO negozi.fornitori (piva, nome_fornitore, indirizzo, email, telefono)
                        VALUES (?, ?, NULLIF(?,''), NULLIF(?,''), NULLIF(?,''))",
                        [$piva, $ragione_sociale, $indirizzo, $email, $telefono]);

            $_SESSION['message'] = "Fornitore creato correttamente.";
            header('Location: gestione_fornitori.php');
            exit;
        }

        // Elimina fornitore
        if ($_POST['action'] === 'delete') {
            $piva_delete = trim($_POST['piva'] ?? '');
            if (empty($piva_delete)) {
                throw new RuntimeException('P.IVA fornitore non valida.');
            }

            $db->query("DELETE FROM negozi.fornitori WHERE piva = ?", [$piva_delete]);

            $_SESSION['message'] = "Fornitore eliminato correttamente.";
            header('Location: gestione_fornitori.php');
            exit;
        }


    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'duplicate key') !== false) {
            $error = 'Partita IVA già esistente.';
        } elseif (strpos($e->getMessage(), 'violates foreign key') !== false) {
            $error = 'Impossibile eliminare: il fornitore ha ordini o prodotti associati.';
        } else {
            $error = 'Errore: ' . $e->getMessage();
        }
    }
}

// Carica dati del fornitore se siamo in modalità modifica
$fornitore = [];
if ($action === 'modifica' && isset($_GET['piva'])) {
    $piva_edit = trim($_GET['piva']);
    try {
        $db = getDB();
        $stmt = $db->query("SELECT piva, nome_fornitore, indirizzo, email, telefono
                            FROM negozi.fornitori WHERE piva = ?", [$piva_edit]);
        $fornitore = $stmt->fetch();

        if (!$fornitore) {
            $error = "Fornitore non trovato.";
            $action = '';
        }
    } catch (Exception $e) {
        $error = 'Errore nel caricamento del fornitore: ' . $e->getMessage();
        $action = '';
    }
}

// Carica lista fornitori
try {
    $db = getDB();
    $stmt = $db->query("SELECT f.piva, f.nome_fornitore, f.indirizzo, f.email, f.telefono,
                               COUNT(DISTINCT mf.prodotto) as num_prodotti,
                               SUM(mf.quantita) as totale_disponibile
                        FROM negozi.fornitori f
                        LEFT JOIN negozi.magazzino_fornitore mf ON f.piva = mf.piva_fornitore
                        GROUP BY f.piva, f.nome_fornitore, f.indirizzo, f.email, f.telefono
                        ORDER BY f.nome_fornitore ASC");
    $fornitori = $stmt->fetchAll();
} catch (Exception $e) {
    $error = 'Errore nel caricamento: ' . $e->getMessage();
    $fornitori = [];
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Fornitori - Sistema Negozi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Retro Arcade Theme -->
    <link href="../css/retro-arcade.css" rel="stylesheet">
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

    <!-- Main Content -->
    <div class="container my-5">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard_manager.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Gestione Fornitori</li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2> Gestione Fornitori</h2>
            <?php if ($action !== 'new'): ?>
                <a href="?action=new" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Nuovo Fornitore
                </a>
            <?php endif; ?>
        </div>

        <!-- Messaggi -->
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($action === 'new' || $action === 'modifica'): ?>
            <!-- Form Nuovo/Modifica Fornitore -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h4>
                                <i class="bi <?= $action === 'new' ? 'bi-plus-circle' : 'bi-pencil' ?>"></i>
                                <?= $action === 'new' ? 'Nuovo Fornitore' : 'Modifica Fornitore' ?>
                            </h4>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action" value="<?= $action === 'new' ? 'create' : 'update' ?>">

                                <!-- Partita IVA -->
                                <div class="mb-3">
                                    <label for="piva" class="form-label">Partita IVA*</label>
                                    <input type="text" class="form-control" id="piva" name="piva"
                                           pattern="\d{11}" maxlength="11"
                                           placeholder="11 cifre numeriche"
                                           value="<?= htmlspecialchars($_POST['piva'] ?? $fornitore['piva'] ?? '') ?>"
                                           <?= $action === 'modifica' ? 'readonly' : 'required' ?>>
                                    <?php if ($action === 'modifica'): ?>
                                        <small class="text-muted">La P.IVA non può essere modificata.</small>
                                    <?php endif; ?>
                                </div>

                                <!-- Ragione Sociale -->
                                <div class="mb-3">
                                    <label for="ragione_sociale" class="form-label">Ragione Sociale*</label>
                                    <input type="text" class="form-control" id="ragione_sociale" name="ragione_sociale"
                                           value="<?= htmlspecialchars($_POST['ragione_sociale'] ?? $fornitore['nome_fornitore'] ?? '') ?>" required>
                                </div>

                                <!-- Indirizzo -->
                                <div class="mb-3">
                                    <label for="indirizzo" class="form-label">Indirizzo</label>
                                    <input type="text" class="form-control" id="indirizzo" name="indirizzo"
                                           value="<?= htmlspecialchars($_POST['indirizzo'] ?? $fornitore['indirizzo'] ?? '') ?>">
                                </div>

                                <!-- Email e Telefono -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email"
                                                   value="<?= htmlspecialchars($_POST['email'] ?? $fornitore['email'] ?? '') ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="telefono" class="form-label">Telefono</label>
                                            <input type="tel" class="form-control" id="telefono" name="telefono"
                                                   value="<?= htmlspecialchars($_POST['telefono'] ?? $fornitore['telefono'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- Bottoni -->
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-check-circle"></i>
                                        <?= $action === 'new' ? 'Crea Fornitore' : 'Aggiorna Fornitore' ?>
                                    </button>
                                    <a href="gestione_fornitori.php" class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i> Annulla
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Lista Fornitori -->
            <div class="card">
                <div class="card-header">
                    <h5> Lista Fornitori</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($fornitori)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                            <p class="mt-3 text-muted">Nessun fornitore trovato.</p>
                            <a href="?action=new" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Aggiungi il primo fornitore
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>P.IVA</th>
                                        <th>Nome Fornitore</th>
                                        <th>Contatti</th>
                                        <th class="text-center">Prodotti</th>
                                        <th class="text-center">Magazzino</th>
                                        <th class="text-center">Ordini</th>
                                        <th class="text-center">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fornitori as $f): ?>
                                        <tr>
                                            <td>
                                                <code><?= htmlspecialchars($f['piva']) ?></code>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($f['nome_fornitore']) ?></strong>
                                                <?php if ($f['indirizzo']): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($f['indirizzo']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($f['email']): ?>
                                                    <a href="mailto:<?= htmlspecialchars($f['email']) ?>">
                                                        <i class="bi bi-envelope"></i> <?= htmlspecialchars($f['email']) ?>
                                                    </a><br>
                                                <?php endif; ?>
                                                <?php if ($f['telefono']): ?>
                                                    <a href="tel:<?= htmlspecialchars($f['telefono']) ?>">
                                                        <i class="bi bi-telephone"></i> <?= htmlspecialchars($f['telefono']) ?>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (!$f['email'] && !$f['telefono']): ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info"><?= (int)$f['num_prodotti'] ?></span>
                                            </td>
                                            <td class="text-center">
                                                <a href="magazzino_fornitore.php?piva=<?= urlencode($f['piva']) ?>"
                                                   class="btn btn-sm btn-outline-info" title="Visualizza magazzino">
                                                    <i class="bi bi-box-seam-fill"></i> <?= (int)($f['totale_disponibile'] ?? 0) ?> pz
                                                </a>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-success"
                                                        onclick="mostraOrdini('<?= htmlspecialchars($f['piva']) ?>', '<?= htmlspecialchars(addslashes($f['nome_fornitore'])) ?>')">
                                                    <i class="bi bi-box-seam"></i> Ordini
                                                </button>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" title="Modifica"
                                                            onclick="modificaFornitore('<?= htmlspecialchars($f['piva']) ?>')">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" title="Elimina"
                                                            onclick="eliminaFornitore('<?= htmlspecialchars($f['piva']) ?>', '<?= htmlspecialchars(addslashes($f['nome_fornitore'])) ?>')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
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

    <!-- Modal Ordini Fornitore -->
    <div class="modal fade" id="modalOrdini" tabindex="-1" aria-labelledby="modalOrdiniLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalOrdiniLabel">
                        <i class="bi bi-box-seam"></i> Ordini Fornitore
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3"><strong>Fornitore:</strong> <span id="ordini_nome_fornitore"></span></p>
                    <div id="ordini_container">
                        <div class="text-center py-4">
                            <div class="spinner-border text-success" role="status">
                                <span class="visually-hidden">Caricamento...</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function modificaFornitore(piva) {
            window.location.href = 'gestione_fornitori.php?action=modifica&piva=' + encodeURIComponent(piva);
        }

        function eliminaFornitore(piva, ragioneSociale) {
            if (confirm('Sei sicuro di voler eliminare il fornitore "' + ragioneSociale + '"?\n\nATTENZIONE: Questa operazione non può essere annullata.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="piva" value="${piva}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function mostraOrdini(piva, nomeFornitore) {
            document.getElementById('ordini_nome_fornitore').textContent = nomeFornitore;
            document.getElementById('ordini_container').innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Caricamento...</span>
                    </div>
                </div>
            `;

            const modal = new bootstrap.Modal(document.getElementById('modalOrdini'));
            modal.show();

            // Carica ordini via AJAX
            fetch('api/ordini_fornitore.php?piva=' + encodeURIComponent(piva))
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        document.getElementById('ordini_container').innerHTML = `
                            <div class="alert alert-danger">${data.error}</div>
                        `;
                        return;
                    }

                    if (data.ordini.length === 0) {
                        document.getElementById('ordini_container').innerHTML = `
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                <p class="mt-2">Nessun ordine per questo fornitore.</p>
                            </div>
                        `;
                        return;
                    }

                    let html = `
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Fornitore</th>
                                        <th>Prodotto</th>
                                        <th>Negozio</th>
                                        <th>Quantità</th>
                                        <th>Data Ordine</th>
                                        <th>Stato</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;

                    data.ordini.forEach(ordine => {
                        let statoBadge = 'secondary';
                        if (ordine.stato_ordine === 'consegnato') statoBadge = 'success';
                        else if (ordine.stato_ordine === 'emesso') statoBadge = 'warning';
                        else if (ordine.stato_ordine === 'annullato') statoBadge = 'danger';

                        html += `
                            <tr>
                                <td>${ordine.nome_fornitore}</td>
                                <td>${ordine.nome_prodotto || 'N/D'}</td>
                                <td>${ordine.nome_negozio || 'N/D'}</td>
                                <td>${ordine.quantita}</td>
                                <td>${ordine.data_ordine}</td>
                                <td><span class="badge bg-${statoBadge}">${ordine.stato_ordine}</span></td>
                            </tr>
                        `;
                    });

                    html += '</tbody></table></div>';
                    document.getElementById('ordini_container').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('ordini_container').innerHTML = `
                        <div class="alert alert-danger">Errore nel caricamento degli ordini.</div>
                    `;
                });
        }
    </script>
</body>
</html>
