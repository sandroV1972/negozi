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
unset($_SESSION['message']); // Rimuovi dopo averlo letto

$error = '';
$action = $_GET['action'] ?? '';

// Gestione azioni POST gestione_negozi.php?action=create update o delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Verifica CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token non valido. Possibile attacco CSRF.');
    }
    $nome_negozio = trim($_POST['nome_negozio'] ?? '');
    $indirizzo = trim($_POST['indirizzo'] ?? '');
    $responsabile = trim($_POST['responsabile'] ?? '');
    $orario_apertura_am = trim($_POST['orario_apertura_am'] ?? '');
    $orario_chiusura_am = trim($_POST['orario_chiusura_am'] ?? '');
    $orario_apertura_pm = trim($_POST['orario_apertura_pm'] ?? '');
    $orario_chiusura_pm = trim($_POST['orario_chiusura_pm'] ?? '');
    $giorni_apertura = isset($_POST['giorni_apertura']) ? $_POST['giorni_apertura'] : [];

    try {
        $db = getDB();
        
        // Aggiorna negozio
        if ($_POST['action'] === 'update') {
            if (empty($nome_negozio) || empty($indirizzo) || empty($responsabile)) {
                throw new RuntimeException('Negozio, indirizzo e responsabile sono obbligatori.');
            }

            // ID negozio viene dall'URL, non dal POST
            $id_negozio = (int)($_GET['id'] ?? 0);
            if ($id_negozio <= 0) {
                throw new RuntimeException('ID negozio non valido.');
            }

            // Inizia transazione per aggiornare sia negozio che orari
            $db->query("BEGIN");

            try {
                // 1. Aggiorna nome negozio in negozi.negozi
                $db->query("UPDATE negozi.negozi SET nome_negozio = ?, indirizzo = ?, responsabile = ?
                            WHERE id_negozio =  ?;",
                            [$nome_negozio, $indirizzo, $responsabile, $id_negozio]);

                // 2. Aggiorna orari negozio in negozi.orari (solo se forniti)
                if (!empty($orario_apertura_am) && !empty($orario_chiusura_am)) {
                    for ($dow = 1; $dow <= 5; $dow++) {
                        $db->query("UPDATE negozi.orari SET apertura = ?, chiusura = ?
                            WHERE negozio = ? AND iod = 1 AND dow = ?;",
                            [$orario_apertura_am, $orario_chiusura_am, $id_negozio, $dow]);
                    }
                }
                if (!empty($orario_apertura_pm) && !empty($orario_chiusura_pm)) {
                    for ($dow = 1; $dow <= 5; $dow++) {
                        $db->query("UPDATE negozi.orari SET apertura = ?, chiusura = ?
                            WHERE negozio = ? AND iod = 2 AND dow = ?;",
                            [$orario_apertura_pm, $orario_chiusura_pm, $id_negozio, $dow]);
                    }
                }

                // Conferma transazione
                $db->query("COMMIT");

                // Redirect per evitare re-submit e pulire $_POST (Pattern PRG: Post-Redirect-Get)
                $_SESSION['message'] = "Negozio modificato correttamente.";
                header('Location: gestione_negozi.php');
                exit;

            } catch (Exception $e) {
                // Rollback in caso di errore
                $db->query("ROLLBACK");
                throw $e;
            }
        }

        else if ($_POST['action'] === 'create') {
            if (empty($nome_negozio) || empty($indirizzo) || empty($responsabile)) {
                throw new RuntimeException('Negozio, indirizzo e responsabile sono obbligatori.');
            }

            // Inizia transazione per creare sia utente che cliente
            $db->query("BEGIN");

            try {
                // 1. Crea negozio in negozi.negozi
                $db->query("INSERT INTO negozi.negozi (nome_negozio, indirizzo, responsabile) VALUES (?, ?, ?)",
                    [$nome_negozio, $indirizzo, $responsabile]);

                $id_negozio = $db->lastInsertId('negozi.negozi_id_negozio_seq');

                // 2. Crea orari di default per il negozio in negozi.orari
                for ($dow = 1; $dow < 6; $dow++) {
                    // Mattina
                    $db->query("INSERT INTO negozi.orari (negozio, dow, iod, apertura, chiusura)
                                VALUES (?, ?, 1, '09:30', '13:00')",
                                [$id_negozio, $dow]);
                    // Pomeriggio
                    $db->query("INSERT INTO negozi.orari (negozio, dow, iod, apertura, chiusura)
                                VALUES (?, ?, 2, '15:30', '19:30')",
                                [$id_negozio, $dow]);
                }   

                // Conferma transazione
                $db->query("COMMIT");

                // Redirect per evitare re-submit e pulire $_POST (Pattern PRG: Post-Redirect-Get)
                $_SESSION['message'] = "Negozio creato!";
                header('Location: gestione_negozi.php');
                exit;
                
            } catch (Exception $e) {
                // Rollback in caso di errore
                $db->query("ROLLBACK");
                throw $e;
            }
        }

        else if ($_POST['action'] === 'delete') {
            $id_negozio = (int)($_POST['id_negozio'] ?? 0);
            if ($id_negozio <= 0) {
                throw new RuntimeException('ID negozio non valido.');
            }

            $db->query("BEGIN");
            try {
                // 1. Elimina orari associati
                $db->query("DELETE FROM negozi.orari WHERE negozio = ?", [$id_negozio]);

                // 2. Elimina negozio
                $db->query("UPDATE negozi.negozi SET attivo = FALSE WHERE id_negozio = ?", [$id_negozio]);

                // Conferma transazione
                $db->query("COMMIT");
            } catch (Exception $e) {
                // Rollback in caso di errore
                $db->query("ROLLBACK");
                throw $e;
            }

            // Redirect per evitare re-submit e pulire $_POST
            $_SESSION['message'] = "Negozio eliminato correttamente.";
            header('Location: gestione_negozi.php');
            exit;
        }
        
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'duplicate key') !== false) {
            $error = 'Negozio già esistente.';
        } else {
            $error = 'Errore: ' . $e->getMessage();
        }
    }
}

// Carica dati del negozio se siamo in modalità modifica (GET, non POST!)
$negozio = [];
$orari = ['apertura_am' => '', 'chiusura_am' => '', 'apertura_pm' => '', 'chiusura_pm' => ''];
if ($action === 'modifica' && isset($_GET['id'])) {
    $id_negozio = (int)$_GET['id'];
    try {
        $db = getDB();
        $stmt = $db->query("SELECT n.id_negozio, n.nome_negozio, n.indirizzo, n.responsabile
                            FROM negozi.negozi n
                            WHERE n.id_negozio = ?", [$id_negozio]);
        $negozio = $stmt->fetch();

        if (!$negozio) {
            $error = "Negozio non trovato.";
            $action = ''; // Torna alla lista
        } else {
            // Carica orari esistenti (prendo il primo giorno disponibile come riferimento)
            // iod = 1 -> mattina (AM), iod = 2 -> pomeriggio (PM)
            $stmt = $db->query("SELECT iod, apertura, chiusura FROM negozi.orari
                                WHERE negozio = ? ORDER BY dow, iod LIMIT 2", [$id_negozio]);
            $orariRows = $stmt->fetchAll();
            foreach ($orariRows as $row) {
                if ($row['iod'] == 1) { // Mattina
                    $orari['apertura_am'] = substr($row['apertura'], 0, 5);
                    $orari['chiusura_am'] = substr($row['chiusura'], 0, 5);
                } else if ($row['iod'] == 2) { // Pomeriggio
                    $orari['apertura_pm'] = substr($row['apertura'], 0, 5);
                    $orari['chiusura_pm'] = substr($row['chiusura'], 0, 5);
                }
            }
        }
    } catch (Exception $e) {
        $error = 'Errore nel caricamento del negozio: ' . $e->getMessage();
        $action = ''; // Torna alla lista
    }
}

// Carica lista Negozi dalla view (oppure query diretta se la view non esiste)
try {
    if ($action !== 'new' && $action !== 'modifica') {
        $db = getDB();

        // Query diretta negozi con orari
        $stmt = $db->query("SELECT n.id_negozio, n.nome_negozio, n.indirizzo, n.responsabile
                            FROM negozi.negozi n
                            WHERE n.attivo = TRUE
                            ORDER BY n.id_negozio ASC;");
        $negozi = $stmt->fetchAll();
    }
} catch (Exception $e) {
    $error = 'Errore nel caricamento: ' . $e->getMessage();
    $negozi = [];
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Negozi - Retro Gaming</title>
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

    <div class="container my-5">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard_manager.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Gestione Negozi</li>
            </ol>
        </nav>
        <!-- Header con pulsante azione -->
        <div class="row mb-4">
            <div class="col">
                <h2>Gestione Negozi</h2>
            </div>
            <div class="col-auto">
            <?php if ($action !== 'new'): ?>
                <a href="?action=new" class="btn btn-primary">
                    <i class="bi bi-plus"></i> Nuovo Negozio
                </a>
            <?php endif; ?>
            </div>
        </div>

        <!-- Alert messaggi -->
        <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Lista negozi - Vista Card -->
        <?php if ($action === 'new' || $action === 'modifica'): ?>
        <!-- Form Nuovo Negozio -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="bi <?= $action === 'new' ? 'bi-shop' : 'bi-shop-window' ?>"></i> <?= $action === 'new' ? 'Nuovo Negozio' : 'Modifica Negozio' ?></h4>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action" value="<?= $action== "new" ? "create" : "update" ?>">
                                <!-- Nome e Indirizzo Negozio -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="nome_negozio" class="form-label">Nome Negozio*</label>
                                            <input type="text" class="form-control" id="nome_negozio" name="nome_negozio"
                                                   value="<?= htmlspecialchars($_POST['nome_negozio'] ?? $negozio['nome_negozio'] ?? '') ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="indirizzo" class="form-label">Indirizzo*</label>
                                            <input type="text" class="form-control" id="indirizzo" name="indirizzo"
                                                   value="<?= htmlspecialchars($_POST['indirizzo'] ?? $negozio['indirizzo'] ?? '') ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <!-- Responsabile -->
                                <div class="mb-3">
                                    <label for="responsabile" class="form-label">Responsabile*</label>
                                    <input type="text" class="form-control" id="responsabile" name="responsabile"
                                           value="<?= htmlspecialchars($_POST['responsabile'] ?? $negozio['responsabile'] ?? '') ?>" required>
                                </div>
                                <!-- Orari Apertura -->
                                <h6 class="mt-4 mb-3"><i class="bi bi-clock"></i> Orari di Apertura - da Lunedì a Venerdì</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card mb-3">
                                            <div class="card-header bg-light">Mattina</div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-6">
                                                        <label for="orario_apertura_am" class="form-label">Apertura</label>
                                                        <input type="time" class="form-control" id="orario_apertura_am" name="orario_apertura_am"
                                                               value="<?= htmlspecialchars($_POST['orario_apertura_am'] ?? $orari['apertura_am'] ?? '09:30') ?>">
                                                    </div>
                                                    <div class="col-6">
                                                        <label for="orario_chiusura_am" class="form-label">Chiusura</label>
                                                        <input type="time" class="form-control" id="orario_chiusura_am" name="orario_chiusura_am"
                                                               value="<?= htmlspecialchars($_POST['orario_chiusura_am'] ?? $orari['chiusura_am'] ?? '13:00') ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card mb-3">
                                            <div class="card-header bg-light">Pomeriggio</div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-6">
                                                        <label for="orario_apertura_pm" class="form-label">Apertura</label>
                                                        <input type="time" class="form-control" id="orario_apertura_pm" name="orario_apertura_pm"
                                                               value="<?= htmlspecialchars($_POST['orario_apertura_pm'] ?? $orari['apertura_pm'] ?? '15:30') ?>">
                                                    </div>
                                                    <div class="col-6">
                                                        <label for="orario_chiusura_pm" class="form-label">Chiusura</label>
                                                        <input type="time" class="form-control" id="orario_chiusura_pm" name="orario_chiusura_pm"
                                                               value="<?= htmlspecialchars($_POST['orario_chiusura_pm'] ?? $orari['chiusura_pm'] ?? '19:30') ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Bottoni per Invio Dati-->
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-success">            
                                        <i class="bi bi-check-circle"></i> 
                                        <?= $action === 'new' ? 'Crea Negozio' : 'Aggiorna Negozio' ?>
                                    </button>
                                    <a href="gestione_negozi.php" class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i> Annulla
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Lista Negozi -->
            <div class="card">
                <div class="card-header">
                    <h5>Lista Negozi</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($negozi)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                            <p class="mt-3 text-muted">Nessun negozio trovato nella view negozi.lista_negozi.</p>
                            <a href="?action=new" class="btn btn-primary">
                                <i class="bi bi-shop-plus"></i> Crea il primo negozio
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome Negozio</th>
                                        <th>Indirizzo</th>
                                        <th>Responsabile</th>
                                        <th>Tessere Associate</th>
                                        <th>Listino</th>
                                        <th>Ordini</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($negozi as $negozio): ?>
                                        <tr>
                                            <td>
                                                <span><?= $negozio['id_negozio'] ?></span>
                                            </td>
                                            <td>
                                                <strong><?= $negozio['nome_negozio'] ?></strong>
                                            </td>
                                            <td>
                                                <?= $negozio['indirizzo'] ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?= $negozio['responsabile'] ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                // Conta tessere associate al negozio
                                                try {
                                                    $db = getDB();
                                                    $stmt = $db->query("SELECT COUNT(*) AS count FROM negozi.tessere WHERE negozio_emittente = ?", [$negozio['id_negozio']]);
                                                    $countResult = $stmt->fetch();
                                                    $tessere_count = $countResult ? (int)$countResult['count'] : 0;
                                                } catch (Exception $e) {
                                                    $tessere_count = 0;
                                                }
                                                ?>
                                                <a href="tessere_negozio.php?id=<?= $negozio['id_negozio'] ?>" class="btn btn-outline-info">
                                                    <?= $tessere_count ?>
                                                </a>
                                            </td>
                                            <td>
                                                <a href="listino_negozio.php?id=<?= $negozio['id_negozio'] ?>" class="btn btn-outline-secondary">
                                                    <i class="bi bi-box-seam"></i> Listino
                                                </a>
                                            </td>
                                            <td>
                                                <?php
                                                // Conta ordini in atto per il negozio
                                                try {
                                                    $db = getDB();
                                                    $stmt = $db->query("SELECT COUNT(*) AS count FROM negozi.ordini_fornitori WHERE negozio = ?
                                                        AND stato_ordine = 'emesso'", [$negozio['id_negozio']]);
                                                    $countResult = $stmt->fetch();
                                                    $ordini_count = $countResult ? (int)$countResult['count'] : 0;
                                                } catch (Exception $e) {
                                                    $ordini_count = 0;
                                                }
                                                ?>
                                                <a href="javascript:void(0)" onClick="listaOrdiniModal(<?= $negozio['id_negozio'] ?>)" style="cursor:pointer" class="btn btn-outline-info">
                                                    <?= $ordini_count ?>
                                                </a>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" title="Modifica" 
                                                            onclick="modificaNegozio(<?= $negozio['id_negozio'] ?>)">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" title="Elimina" 
                                                            onclick="eliminaNegozio(<?= $negozio['id_negozio'] ?>, '<?= htmlspecialchars($negozio['nome_negozio']) ?>')">
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

    <!-- Modal Lista Ordini -->
    <div class="modal fade" id="listaOrdiniModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="bi bi-truck"></i> Ordini in Attesa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="listaOrdiniBody">
                    <div class="text-center py-4">
                        <div class="spinner-border text-warning" role="status">
                            <span class="visually-hidden">Caricamento...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" id="id_negozio" value="">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function listaOrdiniModal(id_negozio) {
            document.getElementById('id_negozio').value = id_negozio;
            if (id_negozio === 0) {
                return;
            } else {
                // Carica lista ordini via fetch API
                fetch('api/ordini_negozio.php?id_negozio=' + id_negozio)
                    .then(response => response.text())
                    .then(data => {
                        document.getElementById('listaOrdiniBody').innerHTML = data;
                    })
                    .catch(error => {
                        document.getElementById('listaOrdiniBody').innerHTML = '<div class="alert alert-danger">Errore nel caricamento degli ordini.</div>';
                    });
            }
            if (!document.getElementById('listaOrdiniModal').classList.contains('show')) {
                var listaOrdiniModal = new bootstrap.Modal(document.getElementById('listaOrdiniModal'));
                listaOrdiniModal.show();
            }

        }
        function modificaNegozio(id) {
        
            window.location.href = 'gestione_negozi.php?action=modifica&id=' + id;
        }
        
        function eliminaNegozio(id, nome) {
            if (confirm('Sei sicuro di voler eliminare il negozio "' + nome + '"?')) {
                // Crea form nascosto per eliminazione
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id_negozio" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function aggiornaStatoOrdine(id_ordine, nuovo_stato) {
            const conferma = nuovo_stato === 'consegnato'
                ? 'Confermi che l\'ordine è arrivato?'
                : 'Sei sicuro di voler annullare questo ordine?';

            if (confirm(conferma)) {
                const id_negozio = document.getElementById('id_negozio').value;
                const formData = new FormData();
                formData.append('action', nuovo_stato);
                formData.append('id_ordine', id_ordine);

                fetch('api/ordini_negozio.php?id_negozio=' + id_negozio, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    // Ricarica la pagina per aggiornare il conteggio ordini
                    window.location.reload();
                })
                .catch(error => {
                    console.error('Errore fetch:', error);
                    alert('Errore nell\'aggiornamento dello stato');
                });
            }
        }
    </script>

</body>
</html>