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

// Gestione azioni POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Verifica CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token non valido. Possibile attacco CSRF.');
    }

    $nome_prodotto = trim($_POST['nome_prodotto'] ?? '');
    $descrizione = trim($_POST['descrizione'] ?? '');
    $immagine_url = trim($_POST['immagine_url'] ?? '');

    try {
        $db = getDB();

        // Aggiorna prodotto
        if ($_POST['action'] === 'update') {
            if (empty($nome_prodotto)) {
                throw new RuntimeException('Il nome del prodotto è obbligatorio.');
            }

            // ID prodotto viene dall'URL, non dal POST
            $id_prodotto = (int)($_GET['id'] ?? 0);
            if ($id_prodotto <= 0) {
                throw new RuntimeException('ID prodotto non valido.');
            }

            $db->query("UPDATE negozi.prodotti SET nome_prodotto = ?, descrizione = NULLIF(?,''), immagine_url = NULLIF(?,'')
                        WHERE id_prodotto = ?",
                        [$nome_prodotto, $descrizione, $immagine_url, $id_prodotto]);

            // Redirect per evitare re-submit e pulire $_POST (Pattern PRG: Post-Redirect-Get)
            $_SESSION['message'] = "Prodotto modificato correttamente.";
            header('Location: gestione_prodotti.php');
            exit;
        }

        // Crea prodotto
        if ($_POST['action'] === 'create') {
            if (empty($nome_prodotto)) {
                throw new RuntimeException('Il nome del prodotto è obbligatorio.');
            }

            $db->query("INSERT INTO negozi.prodotti (nome_prodotto, descrizione, immagine_url) VALUES (?, NULLIF(?,''), NULLIF(?,''))",
                [$nome_prodotto, $descrizione, $immagine_url]);

            // Redirect per evitare re-submit e pulire $_POST (Pattern PRG: Post-Redirect-Get)
            $_SESSION['message'] = "Prodotto creato correttamente!";
            header('Location: gestione_prodotti.php');
            exit;
        }

        // Elimina prodotto
        if ($_POST['action'] === 'delete') {
            $id_prodotto = (int)($_POST['id_prodotto'] ?? 0);
            if ($id_prodotto <= 0) {
                throw new RuntimeException('ID prodotto non valido.');
            }

            $db->query("DELETE FROM negozi.prodotti WHERE id_prodotto = ?", [$id_prodotto]);

            // Redirect per evitare re-submit e pulire $_POST
            $_SESSION['message'] = "Prodotto eliminato correttamente.";
            header('Location: gestione_prodotti.php');
            exit;
        }

    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'duplicate key') !== false) {
            $error = 'Prodotto già esistente.';
        } else if (strpos($e->getMessage(), 'violates foreign key') !== false) {
            $error = 'Impossibile eliminare: il prodotto è associato a ordini o listini.';
        } else {
            $error = 'Errore: ' . $e->getMessage();
        }
    }
}

// Carica dati del prodotto se siamo in modalità modifica (GET, non POST!)
$prodotto = [];
if ($action === 'modifica' && isset($_GET['id'])) {
    $id_prodotto = (int)$_GET['id'];
    try {
        $db = getDB();
        $stmt = $db->query("SELECT id_prodotto, nome_prodotto, descrizione, immagine_url
                            FROM negozi.prodotti
                            WHERE id_prodotto = ?", [$id_prodotto]);
        $prodotto = $stmt->fetch();

        if (!$prodotto) {
            $error = "Prodotto non trovato.";
            $action = ''; // Torna alla lista
        }
    } catch (Exception $e) {
        $error = 'Errore nel caricamento del prodotto: ' . $e->getMessage();
        $action = ''; // Torna alla lista
    }
}

// Carica lista prodotti
$prodotti = [];
try {
    if ($action !== 'new' && $action !== 'modifica') {
        $db = getDB();
        $stmt = $db->query("SELECT p.id_prodotto, p.nome_prodotto, p.descrizione, p.immagine_url,
                                   COUNT(DISTINCT ln.negozio) as num_negozi
                            FROM negozi.prodotti p
                            LEFT JOIN negozi.listino_negozio ln ON p.id_prodotto = ln.prodotto
                            GROUP BY p.id_prodotto, p.nome_prodotto, p.descrizione, p.immagine_url
                            ORDER BY p.id_prodotto");
        $prodotti = $stmt->fetchAll();
    }
} catch (Exception $e) {
    $error = 'Errore nel caricamento: ' . $e->getMessage();
    $prodotti = [];
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Prodotti - Sistema Negozi</title>
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
                <li class="breadcrumb-item active">Gestione Prodotti</li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Gestione Prodotti</h2>
                <p class="text-muted">Gestisci il catalogo prodotti della catena</p>
            </div>
            <?php if ($action !== 'new'): ?>
                <a href="?action=new" class="btn btn-primary">
                    <i class="bi bi-plus"></i> Nuovo Prodotto
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
            <!-- Form Nuovo/Modifica Prodotto -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="bi <?= $action === 'new' ? 'bi-box-seam' : 'bi-pencil-square' ?>"></i> <?= $action === 'new' ? 'Nuovo Prodotto' : 'Modifica Prodotto' ?></h4>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action" value="<?= $action === 'new' ? 'create' : 'update' ?>">

                                <!-- Nome Prodotto -->
                                <div class="mb-3">
                                    <label for="nome_prodotto" class="form-label">Nome Prodotto*</label>
                                    <input type="text" class="form-control" id="nome_prodotto" name="nome_prodotto"
                                           value="<?= htmlspecialchars($_POST['nome_prodotto'] ?? $prodotto['nome_prodotto'] ?? '') ?>" required>
                                </div>

                                <!-- Descrizione -->
                                <div class="mb-3">
                                    <label for="descrizione" class="form-label">Descrizione</label>
                                    <textarea class="form-control" id="descrizione" name="descrizione" rows="4"><?= htmlspecialchars($_POST['descrizione'] ?? $prodotto['descrizione'] ?? '') ?></textarea>
                                </div>

                                <!-- URL Immagine -->
                                <div class="mb-3">
                                    <label for="immagine_url" class="form-label">URL Immagine</label>
                                    <input type="text" class="form-control" id="immagine_url" name="immagine_url"
                                           value="<?= htmlspecialchars($_POST['immagine_url'] ?? $prodotto['immagine_url'] ?? '') ?>"
                                           placeholder="/images/products/nome-prodotto.jpg">
                                    <div class="form-text">Percorso relativo dell'immagine (es. /images/products/c64.jpg)</div>
                                </div>

                                <!-- Anteprima Immagine -->
                                <?php
                                $img_url = $_POST['immagine_url'] ?? $prodotto['immagine_url'] ?? '';
                                if (!empty($img_url)):
                                ?>
                                <div class="mb-3">
                                    <label class="form-label">Anteprima</label>
                                    <div>
                                        <img src="..<?= htmlspecialchars($img_url) ?>" alt="Anteprima"
                                             class="img-thumbnail" style="max-height: 150px;"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                                        <span class="text-muted" style="display: none;"><i class="bi bi-image-alt" style="font-size: 3rem;"></i> Immagine non trovata</span>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Bottoni per Invio Dati-->
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-check-circle"></i>
                                        <?= $action === 'new' ? 'Crea Prodotto' : 'Aggiorna Prodotto' ?>
                                    </button>
                                    <a href="gestione_prodotti.php" class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i> Annulla
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Lista Prodotti -->
            <div class="card">
                <div class="card-header">
                    <h5>Lista Prodotti</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($prodotti)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                            <p class="mt-3 text-muted">Nessun prodotto trovato nel catalogo.</p>
                            <a href="?action=new" class="btn btn-primary">
                                <i class="bi bi-box-seam"></i> Crea il primo prodotto
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Immagine</th>
                                        <th>Nome Prodotto</th>
                                        <th>Descrizione</th>
                                        <th>In Negozi</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($prodotti as $prod): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-secondary"><?= $prod['id_prodotto'] ?></span>
                                            </td>
                                            <td>
                                                <?php if (!empty($prod['immagine_url'])): ?>
                                                    <img src="..<?= htmlspecialchars($prod['immagine_url']) ?>"
                                                         alt="<?= htmlspecialchars($prod['nome_prodotto']) ?>"
                                                         class="img-thumbnail" style="max-height: 50px;">
                                                    <span class="text-muted" style="display: none;"><i class="bi bi-image" style="font-size: 1.5rem;"></i></span>
                                                <?php else: ?>
                                                    <span class="text-muted"><i class="bi bi-image" style="font-size: 1.5rem;"></i></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($prod['nome_prodotto']) ?></strong>
                                            </td>
                                            <td>
                                                <?php
                                                $desc = $prod['descrizione'] ?? '';
                                                echo htmlspecialchars(strlen($desc) > 80 ? substr($desc, 0, 80) . '...' : $desc);
                                                ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?= $prod['num_negozi'] ?> negozi</span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" title="Modifica"
                                                            onclick="modificaProdotto(<?= $prod['id_prodotto'] ?>)">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" title="Elimina"
                                                            onclick="eliminaProdotto(<?= $prod['id_prodotto'] ?>, '<?= htmlspecialchars(addslashes($prod['nome_prodotto'])) ?>')">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function modificaProdotto(id) {
            window.location.href = 'gestione_prodotti.php?action=modifica&id=' + id;
        }

        function eliminaProdotto(id, nome) {
            if (confirm('Sei sicuro di voler eliminare il prodotto "' + nome + '"?\n\nAttenzione: se il prodotto è associato a ordini o listini, l\'eliminazione fallirà.')) {
                // Crea form nascosto per eliminazione
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id_prodotto" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
