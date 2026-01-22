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
$piva = trim($_GET['piva'] ?? '');
$fornitore = null;
$prodotti = [];
$tutti_prodotti = []; // Tutti i prodotti per il form di aggiunta

if (empty($piva)) {
    header('Location: gestione_fornitori.php');
    exit;
}

// Gestione POST - Aggiungi/Aggiorna prodotto nel magazzino
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Verifica CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token non valido.');
    }

    try {
        $db = getDB();

        if ($_POST['action'] === 'aggiungi_prodotto') {
            $id_prodotto = (int)($_POST['id_prodotto'] ?? 0);
            $quantita = (int)($_POST['quantita'] ?? 0);
            $prezzo = (float)($_POST['prezzo'] ?? 0);

            if ($id_prodotto <= 0 || $quantita <= 0 || $prezzo <= 0) {
                throw new RuntimeException('Tutti i campi sono obbligatori e devono essere maggiori di zero.');
            }

            // Verifica se il prodotto esiste già nel magazzino del fornitore
            $stmt = $db->query("SELECT quantita FROM negozi.magazzino_fornitore
                                WHERE piva_fornitore = ? AND prodotto = ?", [$piva, $id_prodotto]);
            $esistente = $stmt->fetch();

            if ($esistente) {
                // Aggiorna quantità esistente (somma)
                $db->query("UPDATE negozi.magazzino_fornitore
                            SET quantita = quantita + ?, prezzo = ?
                            WHERE piva_fornitore = ? AND prodotto = ?",
                            [$quantita, $prezzo, $piva, $id_prodotto]);
                $_SESSION['message'] = "Quantità aggiornata! Aggiunti $quantita pezzi al magazzino.";
            } else {
                // Inserisci nuovo prodotto
                $db->query("INSERT INTO negozi.magazzino_fornitore (piva_fornitore, prodotto, quantita, prezzo)
                            VALUES (?, ?, ?, ?)",
                            [$piva, $id_prodotto, $quantita, $prezzo]);
                $_SESSION['message'] = "Prodotto aggiunto al magazzino!";
            }

            header("Location: magazzino_fornitore.php?piva=" . urlencode($piva));
            exit;
        }
    } catch (Exception $e) {
        $error = 'Errore: ' . $e->getMessage();
    }
}

try {
    $db = getDB();

    // Recupera dati fornitore
    $stmt = $db->query("SELECT piva, nome_fornitore, indirizzo, email, telefono
                        FROM negozi.fornitori WHERE piva = ?", [$piva]);
    $fornitore = $stmt->fetch();

    if (!$fornitore) {
        header('Location: gestione_fornitori.php');
        exit;
    }

    // Recupera prodotti del magazzino fornitore
    $stmt = $db->query("SELECT mf.prodotto, p.nome_prodotto, p.descrizione, p.immagine_url,
                               mf.quantita, mf.prezzo
                        FROM negozi.magazzino_fornitore mf
                        JOIN negozi.prodotti p ON p.id_prodotto = mf.prodotto
                        WHERE mf.piva_fornitore = ?
                        ORDER BY p.nome_prodotto ASC", [$piva]);
    $prodotti = $stmt->fetchAll();

    // Recupera tutti i prodotti per il form di aggiunta
    $stmt = $db->query("SELECT id_prodotto, nome_prodotto FROM negozi.prodotti ORDER BY nome_prodotto ASC");
    $tutti_prodotti = $stmt->fetchAll();

} catch (Exception $e) {
    $error = 'Errore nel caricamento: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Magazzino Fornitore - <?= htmlspecialchars($fornitore['nome_fornitore'] ?? '') ?></title>
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
                <li class="breadcrumb-item"><a href="gestione_fornitori.php">Gestione Fornitori</a></li>
                <li class="breadcrumb-item active">Magazzino</li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="bi bi-box-seam-fill"></i> Magazzino Fornitore</h2>
                <?php if ($fornitore): ?>
                    <p class="text-muted mb-0">
                        <strong><?= htmlspecialchars($fornitore['nome_fornitore']) ?></strong>
                        (P.IVA: <code><?= htmlspecialchars($fornitore['piva']) ?></code>)
                    </p>
                <?php endif; ?>
            </div>
            <div>
                <a href="gestione_fornitori.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Torna ai Fornitori
                </a>
            </div>
        </div>

        <!-- Alert messaggi -->
        <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Messaggi errore -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Form Aggiungi Prodotto -->
        <?php if (!empty($tutti_prodotti)): ?>
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Aggiungi Prodotto al Magazzino</h5>
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label for="select_prodotto" class="form-label">Seleziona un prodotto</label>
                        <?php
                        // Crea un array associativo dei prodotti già in magazzino con il loro prezzo
                        $prodotti_in_magazzino = [];
                        foreach ($prodotti as $p) {
                            $prodotti_in_magazzino[$p['prodotto']] = $p['prezzo'];
                        }
                        ?>
                        <select class="form-select" id="select_prodotto">
                            <option value="">-- Seleziona prodotto --</option>
                            <?php foreach ($tutti_prodotti as $p):
                                $in_magazzino = isset($prodotti_in_magazzino[$p['id_prodotto']]);
                                $prezzo_esistente = $in_magazzino ? $prodotti_in_magazzino[$p['id_prodotto']] : '';
                            ?>
                                <option value="<?= $p['id_prodotto'] ?>"
                                        data-nome="<?= htmlspecialchars($p['nome_prodotto']) ?>"
                                        data-in-magazzino="<?= $in_magazzino ? '1' : '0' ?>"
                                        data-prezzo="<?= $prezzo_esistente ?>">
                                    <?= htmlspecialchars($p['nome_prodotto']) ?><?= $in_magazzino ? ' (in magazzino)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Per prodotti già in magazzino, la quantità verrà sommata.</div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Lista Prodotti Magazzino -->
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-box"></i> Prodotti Disponibili</h5>
            </div>
            <div class="card-body">
                <?php if (empty($prodotti)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                        <p class="mt-3 text-muted">Nessun prodotto nel magazzino di questo fornitore.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Immagine</th>
                                    <th>Prodotto</th>
                                    <th>Descrizione</th>
                                    <th class="text-center">Quantita</th>
                                    <th class="text-end">Prezzo Unitario</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($prodotti as $p): ?>
                                    <tr>
                                        <td style="width: 80px;">
                                            <?php if ($p['immagine_url']): ?>
                                                <img src="<?= htmlspecialchars($p['immagine_url']) ?>"
                                                     alt="<?= htmlspecialchars($p['nome_prodotto']) ?>"
                                                     class="img-thumbnail" style="max-width: 60px; max-height: 60px;">
                                            <?php else: ?>
                                                <span class="text-muted"><i class="bi bi-image" style="font-size: 2rem;"></i></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($p['nome_prodotto']) ?></strong>
                                        </td>
                                        <td>
                                            <?= $p['descrizione'] ? htmlspecialchars($p['descrizione']) : '<span class="text-muted">-</span>' ?>
                                        </td>
                                        <td class="text-center">
                                            <?php
                                            $qta = (int)$p['quantita'];
                                            $badgeClass = $qta > 10 ? 'bg-success' : ($qta > 0 ? 'bg-warning text-dark' : 'bg-danger');
                                            ?>
                                            <span class="badge <?= $badgeClass ?> fs-6"><?= $qta ?></span>
                                        </td>
                                        <td class="text-end">
                                            <strong>&euro; <?= number_format((float)$p['prezzo'], 2, ',', '.') ?></strong>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-secondary">
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Totale prodotti:</strong></td>
                                    <td class="text-center"><strong><?= count($prodotti) ?></strong></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Totale pezzi disponibili:</strong></td>
                                    <td class="text-center">
                                        <strong><?= array_sum(array_column($prodotti, 'quantita')) ?></strong>
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Aggiungi Prodotto -->
    <div class="modal fade" id="aggiungiProdottoModal" tabindex="-1" aria-labelledby="aggiungiProdottoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="formAggiungiProdotto">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="aggiungi_prodotto">
                    <input type="hidden" name="id_prodotto" id="modal_id_prodotto">

                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="aggiungiProdottoModalLabel">
                            <i class="bi bi-plus-circle"></i> Aggiungi Prodotto al Magazzino
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Prodotto</label>
                            <input type="text" class="form-control" id="modal_nome_prodotto" readonly>
                        </div>

                        <!-- Info prezzo esistente (visibile solo per prodotti già in magazzino) -->
                        <div class="mb-3" id="info_prezzo_esistente" style="display: none;">
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle"></i> Prezzo attuale: <strong id="prezzo_attuale_display">€0.00</strong>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="quantita" class="form-label">Quantità da aggiungere *</label>
                            <input type="number" class="form-control" id="quantita" name="quantita"
                                   min="1" step="1" value="1" required placeholder="Es: 10">
                            <div class="form-text">La quantità verrà sommata a quella esistente.</div>
                        </div>

                        <!-- Prezzo (visibile solo per prodotti NUOVI) -->
                        <div class="mb-3" id="div_prezzo">
                            <label for="prezzo" class="form-label">Prezzo Unitario (€) *</label>
                            <input type="number" class="form-control" id="prezzo" name="prezzo"
                                   min="0.01" step="0.01" placeholder="Es: 99.99">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Aggiungi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Quando si seleziona un prodotto dal dropdown, apre il modal
        document.getElementById('select_prodotto').addEventListener('change', function() {
            const select = this;
            const selectedOption = select.options[select.selectedIndex];
            const prodottoId = select.value;
            const nomeProdotto = selectedOption.getAttribute('data-nome');
            const inMagazzino = selectedOption.getAttribute('data-in-magazzino') === '1';
            const prezzoEsistente = selectedOption.getAttribute('data-prezzo');

            if (prodottoId) {
                // Imposta i valori nel modal
                document.getElementById('modal_id_prodotto').value = prodottoId;
                document.getElementById('modal_nome_prodotto').value = nomeProdotto;
                document.getElementById('quantita').value = 1;

                const divPrezzo = document.getElementById('div_prezzo');
                const infoPrezzoEsistente = document.getElementById('info_prezzo_esistente');
                const inputPrezzo = document.getElementById('prezzo');

                if (inMagazzino) {
                    // Prodotto già in magazzino: nascondi campo prezzo, mostra info prezzo attuale
                    divPrezzo.style.display = 'none';
                    inputPrezzo.removeAttribute('required');
                    inputPrezzo.value = prezzoEsistente; // Passa il prezzo esistente al form
                    infoPrezzoEsistente.style.display = 'block';
                    document.getElementById('prezzo_attuale_display').textContent = '€' + parseFloat(prezzoEsistente).toFixed(2);
                } else {
                    // Prodotto nuovo: mostra campo prezzo, nascondi info
                    divPrezzo.style.display = 'block';
                    inputPrezzo.setAttribute('required', 'required');
                    inputPrezzo.value = '';
                    infoPrezzoEsistente.style.display = 'none';
                }

                // Apre il modal
                const modal = new bootstrap.Modal(document.getElementById('aggiungiProdottoModal'));
                modal.show();
            }
        });

        // Quando il modal si chiude, resetta il dropdown
        document.getElementById('aggiungiProdottoModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('select_prodotto').value = '';
        });
    </script>
</body>
</html>
