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
$negozio = null;
$listino = [];

// Recupera l'ID del negozio dalla query string
$id_negozio = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_negozio <= 0) {
    $error = 'ID negozio non valido.';
} else {
    try {
        $db = getDB();

        // Recupera info negozio
        $stmt = $db->query("SELECT id_negozio, nome_negozio, indirizzo FROM negozi.negozi WHERE id_negozio = ?", [$id_negozio]);
        $negozio = $stmt->fetch();

        if (!$negozio) {
            $error = 'Negozio non trovato.';
        }
    } catch (Exception $e) {
        $error = 'Errore nel caricamento: ' . $e->getMessage();
    }
}

// Gestione azioni POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $negozio) {
    // Verifica CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token non valido. Possibile attacco CSRF.');
    }

    try {
        $db = getDB();


        // Invia un Ordine al fornitore per un prodotto
        if ($_POST['action'] === 'order') {
            $prodotto = (int)($_POST['prodotto'] ?? 0);
            $quantita = (int)($_POST['quantita'] ?? 0);

            if ($prodotto <= 0) {
                throw new RuntimeException('Seleziona un prodotto valido.');
            }
            if ($quantita <= 0) {
                throw new RuntimeException('La quantità deve essere maggiore di zero.');
            }

            try {
                $db->query("BEGIN");

                // Trova il miglior fornitore usando la funzione del database
                $stmt = $db->query("SELECT negozi.miglior_fornitore(?) AS piva_fornitore", [$prodotto]);
                $result = $stmt->fetch();
                $fornitore = $result['piva_fornitore'] ?? null;

                if (empty($fornitore)) {
                    throw new RuntimeException('Nessun fornitore disponibile per questo prodotto con la quantità richiesta.');
                }

                $db->query("INSERT INTO negozi.ordini_fornitori (negozio, prodotto, fornitore, quantita, data_ordine, data_consegna, stato_ordine)
                            VALUES (?, ?, ?::char(11), ?, ?, ?, 'emesso')",
                    [$id_negozio, $prodotto, $fornitore, $quantita, date('Y-m-d'), date('Y-m-d', strtotime('+7 days'))]);

                // La quantità nel magazzino del fornitore viene aggiornata con funzione trigger su ordini_fornitore
                $db->query("COMMIT");

                $_SESSION['message'] = "Ordine effettuato con successo!";
                header("Location: listino_negozio.php?id=$id_negozio");
                exit;
            } catch (Exception $e) {
                $db->query("ROLLBACK");
                throw $e;
            }
        }

        // Aggiungi prodotto al listino
        if ($_POST['action'] === 'add') {
            $prodotto = (int)($_POST['prodotto'] ?? 0);
            $prezzo = (float)($_POST['prezzo'] ?? 0);

            if ($prodotto <= 0) {
                throw new RuntimeException('Seleziona un prodotto valido.');
            }
            if ($prezzo <= 0) {
                throw new RuntimeException('Il prezzo deve essere maggiore di zero.');
            }

            $db->query("INSERT INTO negozi.listino_negozio (negozio, prodotto, prezzo_listino) VALUES (?, ?, ?)",
                [$id_negozio, $prodotto, $prezzo]);

            $_SESSION['message'] = "Prodotto aggiunto al listino.";
            header("Location: listino_negozio.php?id=$id_negozio");
            exit;
        }

        // Aggiorna prezzo
        if ($_POST['action'] === 'update') {
            $prodotto = (int)($_POST['prodotto'] ?? 0);
            $prezzo = (float)($_POST['prezzo'] ?? 0);

            if ($prodotto <= 0 || $prezzo <= 0) {
                throw new RuntimeException('Dati non validi.');
            }

            $db->query("UPDATE negozi.listino_negozio SET prezzo_listino = ? WHERE negozio = ? AND prodotto = ?",
                [$prezzo, $id_negozio, $prodotto]);

            $_SESSION['message'] = "Prezzo aggiornato.";
            header("Location: listino_negozio.php?id=$id_negozio");
            exit;
        }

        // Rimuovi prodotto dal listino
        if ($_POST['action'] === 'delete') {
            $prodotto = (int)($_POST['prodotto'] ?? 0);

            if ($prodotto <= 0) {
                throw new RuntimeException('Prodotto non valido.');
            }

            $db->query("DELETE FROM negozi.listino_negozio WHERE negozio = ? AND prodotto = ?",
                [$id_negozio, $prodotto]);

            $_SESSION['message'] = "Prodotto rimosso dal listino.";
            header("Location: listino_negozio.php?id=$id_negozio");
            exit;
        }

    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'duplicate key') !== false) {
            $error = 'Questo prodotto è già nel listino.';
        } else {
            $error = 'Errore: ' . $e->getMessage();
        }
    }
}

// Carica listino del negozio
if ($negozio) {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT ln.prodotto, p.nome_prodotto, p.descrizione, p.immagine_url, ln.prezzo_listino, ln.magazzino
                            FROM negozi.listino_negozio ln
                            JOIN negozi.prodotti p ON ln.prodotto = p.id_prodotto
                            WHERE ln.negozio = ?
                            ORDER BY p.nome_prodotto", [$id_negozio]);
        $listino = $stmt->fetchAll();

        // Carica prodotti NON ancora nel listino per il dropdown "Aggiungi"
        $stmt = $db->query("SELECT id_prodotto, nome_prodotto FROM negozi.prodotti
                            WHERE id_prodotto NOT IN (SELECT prodotto FROM negozi.listino_negozio WHERE negozio = ?)
                            ORDER BY nome_prodotto", [$id_negozio]);
        $prodotti_disponibili = $stmt->fetchAll();
    } catch (Exception $e) {
        $error = 'Errore nel caricamento del listino: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listino Negozio - Sistema Negozi</title>
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
                <li class="breadcrumb-item active">Listino</li>
            </ol>
        </nav>

        <?php if (!empty($error)): ?>
        <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php elseif ($negozio): ?>

        <!-- Messaggi -->
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="row mb-4">
            <div class="col">
                <h2>Listino Negozio</h2>
                <p class="text-muted">
                    <strong><?= htmlspecialchars($negozio['nome_negozio']) ?></strong> -
                    <?= htmlspecialchars($negozio['indirizzo']) ?>
                </p>
            </div>
        </div>

        <!-- Form Aggiungi Prodotto -->
        <?php if (!empty($prodotti_disponibili)): ?>
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Aggiungi Prodotto al Listino</h5>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3 align-items-end">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="add">
                    <div class="col-md-6">
                        <label for="prodotto" class="form-label">Prodotto</label>
                        <select class="form-select" id="prodotto" name="prodotto" required>
                            <option value="">Seleziona un prodotto...</option>
                            <?php foreach ($prodotti_disponibili as $prod): ?>
                                <option value="<?= $prod['id_prodotto'] ?>"><?= htmlspecialchars($prod['nome_prodotto']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="prezzo" class="form-label">Prezzo (€)</label>
                        <input type="number" step="0.01" min="0.01" class="form-control" id="prezzo" name="prezzo" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-plus"></i> Aggiungi
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Lista Prodotti nel Listino -->
        <div class="card">
            <div class="card-header ">
                <h5>
                    Prodotti in Listino (<?= count($listino) ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($listino)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                        <p class="mt-3 text-muted">Nessun prodotto nel listino di questo negozio.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Immagine</th>
                                    <th>Prodotto</th>
                                    <th>Descrizione</th>
                                    <th>Prezzo</th>
                                    <th>Magazzino</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($listino as $item): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($item['immagine_url'])): ?>
                                                <img src="..<?= htmlspecialchars($item['immagine_url']) ?>"
                                                     alt="<?= htmlspecialchars($item['nome_prodotto']) ?>"
                                                     class="img-thumbnail" style="max-height: 50px;"
                                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                                                <span class="text-muted" style="display: none;"><i class="bi bi-image" style="font-size: 1.5rem;"></i></span>
                                            <?php else: ?>
                                                <span class="text-muted"><i class="bi bi-image" style="font-size: 1.5rem;"></i></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($item['nome_prodotto']) ?></strong>
                                        </td>
                                        <td>
                                            <?php
                                            $desc = $item['descrizione'] ?? '';
                                            echo htmlspecialchars(strlen($desc) > 60 ? substr($desc, 0, 60) . '...' : $desc);
                                            ?>
                                        </td>
                                        <td>
                                            <form method="POST" class="d-flex gap-1" style="min-width: 150px;">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="prodotto" value="<?= $item['prodotto'] ?>">
                                                <input type="number" step="0.01" min="0.01" name="prezzo"
                                                       value="<?= number_format($item['prezzo_listino'], 2, '.', '') ?>"
                                                       class="form-control form-control-sm" style="width: 90px;">
                                                <button type="submit" class="btn btn-sm btn-outline-primary" title="Aggiorna prezzo">
                                                    <i class="bi bi-check"></i>
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span style="min-width: 40px; text-align: right;"><?= (int)$item['magazzino'] ?></span>
                                                <button class="btn btn-sm btn-outline-success" title="Ordina da fornitore"
                                                        onclick="apriModalOrdine(<?= $item['prodotto'] ?>, '<?= htmlspecialchars(addslashes($item['nome_prodotto'])) ?>')">
                                                    <i class="bi bi-plus"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-danger" title="Rimuovi dal listino"
                                                    onclick="rimuoviProdotto(<?= $item['prodotto'] ?>, '<?= htmlspecialchars(addslashes($item['nome_prodotto'])) ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
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

    <!-- Modal Ordine Fornitore -->
    <div class="modal fade" id="modalOrdine" tabindex="-1" aria-labelledby="modalOrdineLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalOrdineLabel">
                        <i class="bi bi-truck"></i> Ordina da Fornitore
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="formOrdine">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="order">
                        <input type="hidden" name="prodotto" id="ordine_prodotto">

                        <div class="mb-3">
                            <label class="form-label">Prodotto</label>
                            <input type="text" class="form-control" id="ordine_nome_prodotto" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="ordine_quantita" class="form-label">Quantità</label>
                            <input type="number" class="form-control" id="ordine_quantita" name="quantita" min="1" value="1" required>
                            <div class="form-text">Il fornitore con il miglior prezzo verrà selezionato automaticamente.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-cart-plus"></i> Effettua Ordine
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function rimuoviProdotto(prodottoId, nomeProdotto) {
            if (confirm('Sei sicuro di voler rimuovere "' + nomeProdotto + '" dal listino?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="prodotto" value="${prodottoId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function apriModalOrdine(prodottoId, nomeProdotto) {
            document.getElementById('ordine_prodotto').value = prodottoId;
            document.getElementById('ordine_nome_prodotto').value = nomeProdotto;
            document.getElementById('ordine_quantita').value = 1;

            const modal = new bootstrap.Modal(document.getElementById('modalOrdine'));
            modal.show();
        }
    </script>
</body>
</html>
