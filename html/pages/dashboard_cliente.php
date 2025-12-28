<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_tipo'] !== 'cliente') {
    header('Location: ../index.php?error=access_denied');
    exit;
}

require_once '../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Inizializza carrello
if (!isset($_SESSION['carrello'])) {
    // il carrello è un array associativo con chiave "prodottoID_negozioID"
    $_SESSION['carrello'] = [];
}

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
$error = '';

// Recupera dati cliente
$id_cliente = null;
$id_tessera = null;
$punti_disponibili = 0;

try {
    $db = getDB();
    $stmt = $db->query("SELECT id_cliente, tessera FROM negozi.clienti WHERE utente = ?", [$_SESSION['user_id']]);
    $cliente_data = $stmt->fetch();
    if ($cliente_data) {
        // assegna variabili globali per id_cliente e id_tessera
        $id_cliente = $cliente_data['id_cliente'];
        $id_tessera = $cliente_data['tessera'];
        if ($id_tessera) {
            $stmt = $db->query("SELECT saldo_punti FROM negozi.tessere WHERE id_tessera = ?", [$id_tessera]);
            $tessera = $stmt->fetch();
            // assegna punti disponibili alla variabile globale $punti_disponibili del cliente
            $punti_disponibili = $tessera ? (int)$tessera['saldo_punti'] : 0;
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Gestione azioni POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token non valido.');
    }

    $action = $_POST['action'];

    //
    // Aggiungi al carrello (gestito con sessione)
    // il carrello è un array associativo con chiave "prodottoID_negozioID"
    // ogni voce contiene: prodotto_id, negozio_id, nome_prodotto, nome_negozio, prezzo, quantita, magazzino
    // add_cart ha come parametri: prodotto, negozio, quantita che derviano dalla selezione del cliente
    // nella pagina dashboard_cliente.php
    //
    if ($action === 'add_cart') {
        $prodotto_id = (int)($_POST['prodotto'] ?? 0);
        $negozio_id = (int)($_POST['negozio'] ?? 0);
        $quantita = (int)($_POST['quantita'] ?? 1);

        if ($prodotto_id > 0 && $negozio_id > 0 && $quantita > 0) {
            try {
                // Verifica disponibilità dal database e ricava dati prodotto:
                // nome_prodotto, nome_negozio, prezzo_listino, magazzino per completare i dati del carrello
                $db = getDB();
                $stmt = $db->query("SELECT ln.prezzo_listino, ln.magazzino, p.nome_prodotto, n.nome_negozio
                                    FROM negozi.listino_negozio ln
                                    JOIN negozi.prodotti p ON ln.prodotto = p.id_prodotto
                                    JOIN negozi.negozi n ON ln.negozio = n.id_negozio
                                    WHERE ln.negozio = ? AND ln.prodotto = ?", [$negozio_id, $prodotto_id]);
                $prod = $stmt->fetch();
                
                // se vi è disponibilità sufficiente in magazzino del negozio aggiunge al carrello
                if ($prod && $prod['magazzino'] >= $quantita) {
                    $key = $prodotto_id . '_' . $negozio_id;

                    // Calcola quantità già nel carrello per questo prodotto/negozio
                    $quantita_carrello = isset($_SESSION['carrello'][$key]) ? $_SESSION['carrello'][$key]['quantita'] : 0;
                    $nuova_quantita = $quantita_carrello + $quantita;
                    
                    //aggiorna carrello solo se la nuova quantità non supera il magazzino
                    if ($nuova_quantita <= $prod['magazzino']) {
                        $_SESSION['carrello'][$key] = [
                            'prodotto_id' => $prodotto_id,
                            'negozio_id' => $negozio_id,
                            'nome_prodotto' => $prod['nome_prodotto'],
                            'nome_negozio' => $prod['nome_negozio'],
                            'prezzo' => $prod['prezzo_listino'],
                            'quantita' => $nuova_quantita,
                            'magazzino' => $prod['magazzino']
                        ];
                        $_SESSION['message'] = "Aggiunto al carrello: " . $prod['nome_prodotto'] . " x" . $quantita;
                    } else {
                        $_SESSION['message'] = "Errore: disponibili solo " . $prod['magazzino'] . " unità (già " . $quantita_carrello . " nel carrello)";
                    }
                } else {
                    $_SESSION['message'] = "Errore: quantità non disponibile.";
                }
            } catch (Exception $e) {
                $_SESSION['message'] = "Errore: " . $e->getMessage();
            }
        }
        header("Location: dashboard_cliente.php");
        exit;
    }

    // Rimuovi dal carrello
    if ($action === 'remove_cart') {
        $key = $_POST['key'] ?? '';
        if (isset($_SESSION['carrello'][$key])) {
            unset($_SESSION['carrello'][$key]);
            $_SESSION['message'] = "Prodotto rimosso dal carrello.";
        }
        header("Location: dashboard_cliente.php");
        exit;
    }

    // Svuota carrello
    if ($action === 'clear_cart') {
        $_SESSION['carrello'] = [];
        $_SESSION['message'] = "Carrello svuotato.";
        header("Location: dashboard_cliente.php");
        exit;
    }

    // Checkout
    if ($action === 'checkout') {
        if (empty($_SESSION['carrello'])) {
            $_SESSION['message'] = "Il carrello è vuoto.";
            header("Location: dashboard_cliente.php");
            exit;
        } else {
            $carrello = $_SESSION['carrello'];
        }

        try {
            $db = getDB();
            $db->query("BEGIN");

            $punti_guadagnati = 0;

            // Verifica disponibilità e crea fattura

            $totale_fattura = 0;
            $dettagli_fattura = [];

            foreach ($carrello as $item) {
                // Verifica disponibilità attuale
                $stmt = $db->query("SELECT magazzino FROM negozi.listino_negozio
                                        WHERE negozio = ? AND prodotto = ? FOR UPDATE",
                                        [$item['negozio_id'], $item['prodotto_id']]);
                $stock = $stmt->fetch();

                if (!$stock || $stock['magazzino'] < $item['quantita']) {
                    throw new RuntimeException("Disponibilità insufficiente per " . $item['nome_prodotto'] .
                        " presso " . $item['nome_negozio'] . " (disponibili: " . ($stock['magazzino'] ?? 0) . ")");
                }

                $subtotale = $item['prezzo'] * $item['quantita'];

                $dettagli_fattura[] = [
                        'prodotto_id' => $item['prodotto_id'],
                        'nome_prodotto' => $item['nome_prodotto'],
                        'quantita' => $item['quantita'],
                        'prezzo' => $item['prezzo'],
                        'subtotale' => $subtotale
                ];
                $totale_fattura += $subtotale;
            }

            // Crea fattura per questo negozio
            $stmt = $db->query("INSERT INTO negozi.fatture (cliente, totale_pagato)
                                VALUES (?, ?) RETURNING id_fattura",
                                [$id_cliente, $totale_fattura]);
            $fattura = $stmt->fetch();
            $id_fattura = $fattura['id_fattura'];

            // Inserisci dettagli e aggiorna magazzino con la nuova quantità
            foreach ($dettagli_fattura as $det) {
                $db->query("INSERT INTO negozi.dettagli_fattura (fattura, prodotto, quantita, prezzo_unita)
                                VALUES (?, ?, ?, ?)",
                                [$id_fattura, $det['prodotto_id'], $det['quantita'], $det['prezzo']]);

                    $db->query("UPDATE negozi.listino_negozio SET magazzino = magazzino - ?
                                WHERE negozio = ? AND prodotto = ?",
                                [$det['quantita'], $negozio_id, $det['prodotto_id']]);
            }
            

            // Calcola e aggiorna punti: un punto ogni euro speso
            $punti_guadagnati = (int)($totale_generale);
            if ($id_tessera && $punti_guadagnati > 0) {
                $db->query("UPDATE negozi.tessere SET saldo_punti = saldo_punti + ? WHERE id_tessera = ?",
                            [$punti_guadagnati, $id_tessera]);
            }

            $db->query("COMMIT");

            // Salva dati conferma in sessione
            $_SESSION['conferma_acquisto'] = [
                'fattura' => $id_fattura,
                'totale_fattura' => $totale_fattura,
                'punti_guadagnati' => $punti_guadagnati,
                'punti_totali' => $punti_disponibili + $punti_guadagnati
            ];

            // Svuota carrello
            $_SESSION['carrello'] = [];

            header("Location: conferma_acquisto.php");
            exit;

        } catch (Exception $e) {
            $db->query("ROLLBACK");
            $error = $e->getMessage();
        }
    }
}

// Carica tutti i prodotti con disponibilità nei negozi
$prodotti = [];
try {
    $db = getDB();
    $stmt = $db->query("SELECT p.id_prodotto, p.nome_prodotto, p.descrizione, p.immagine_url
                        FROM negozi.prodotti p
                        ORDER BY p.nome_prodotto");
    $prodotti = $stmt->fetchAll();

    // Per ogni prodotto carica i negozi che lo hanno
    foreach ($prodotti as &$prod) {
        $stmt = $db->query("SELECT ln.negozio, n.nome_negozio, ln.prezzo_listino, ln.magazzino
                            FROM negozi.listino_negozio ln
                            JOIN negozi.negozi n ON ln.negozio = n.id_negozio
                            WHERE ln.prodotto = ? AND ln.magazzino > 0
                            ORDER BY ln.prezzo_listino ASC",
                            [$prod['id_prodotto']]);
        $prod['negozi'] = $stmt->fetchAll();
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Calcola totale carrello
$totale_carrello = 0;
$num_articoli = 0;
foreach ($_SESSION['carrello'] as $item) {
    $totale_carrello += $item['prezzo'] * $item['quantita'];
    $num_articoli += $item['quantita'];
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retro Gaming Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard_cliente.php">
                <i class="bi bi-shop"></i> Retro Gaming Store
            </a>
            <div class="navbar-nav ms-auto d-flex align-items-center">
                <button class="btn btn-outline-light btn-sm me-3 position-relative" type="button" data-bs-toggle="offcanvas" data-bs-target="#cartOffcanvas">
                    <i class="bi bi-cart3"></i> Carrello
                    <?php if ($num_articoli > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= $num_articoli ?>
                        </span>
                    <?php endif; ?>
                </button>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i>
                        <?= htmlspecialchars($_SESSION['user_nome']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="cambia_password.php">
                            <i class="bi bi-key"></i> Cambia password
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a></li>
                    </ul>
                </div>
                <?php if ($punti_disponibili > 0): ?>
                    <span class="badge bg-warning text-dark ms-2"><?= $punti_disponibili ?> punti</span>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Carrello Offcanvas -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="cartOffcanvas">
        <div class="offcanvas-header bg-dark text-white">
            <h5 class="offcanvas-title"><i class="bi bi-cart3"></i> Carrello</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <?php if (empty($_SESSION['carrello'])): ?>
                <p class="text-muted text-center">Il carrello è vuoto.</p>
            <?php else: ?>
                <?php foreach ($_SESSION['carrello'] as $key => $item): ?>
                    <div class="card mb-2">
                        <div class="card-body p-2">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong class="small"><?= htmlspecialchars($item['nome_prodotto']) ?></strong>
                                    <br><small class="text-muted"><?= htmlspecialchars($item['nome_negozio']) ?></small>
                                    <br><small><?= $item['quantita'] ?> x <?= number_format($item['prezzo'], 2) ?></small>
                                </div>
                                <div class="text-end">
                                    <strong><?= number_format($item['prezzo'] * $item['quantita'], 2) ?></strong>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="action" value="remove_cart">
                                        <input type="hidden" name="key" value="<?= htmlspecialchars($key) ?>">
                                        <button type="submit" class="btn btn-link btn-sm text-danger p-0 ms-2">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <hr>
                <div class="d-flex justify-content-between mb-3">
                    <strong>Totale:</strong>
                    <strong><?= number_format($totale_carrello, 2) ?></strong>
                </div>
                <div class="small text-muted mb-3">
                    Punti che guadagnerai: <strong><?= (int)($totale_carrello / 10) ?></strong>
                </div>

                <form method="POST" class="d-grid gap-2">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <button type="submit" name="action" value="checkout" class="btn btn-success">
                        <i class="bi bi-bag-check"></i> Acquista tutto
                    </button>
                    <button type="submit" name="action" value="clear_cart" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-trash"></i> Svuota carrello
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="container my-4">
        <div class="text-center mb-4">
            <img src="../images/logo.jpg" alt="Logo" style="max-height: 100px;" class="mb-2">
            <h2>Catalogo Prodotti</h2>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($prodotti)): ?>
            <p class="text-center text-muted">Nessun prodotto disponibile.</p>
        <?php else: ?>
            <div class="row">
                <?php foreach ($prodotti as $prodotto): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <?php if (!empty($prodotto['immagine_url'])): ?>
                                <img src="..<?= htmlspecialchars($prodotto['immagine_url']) ?>"
                                     class="card-img-top" style="height: 150px; object-fit: cover;"
                                     onerror="this.style.display='none'">
                            <?php endif; ?>
                            <div class="card-body">
                                <h6 class="card-title"><?= htmlspecialchars($prodotto['nome_prodotto']) ?></h6>
                                <p class="card-text small text-muted">
                                    <?= htmlspecialchars(substr($prodotto['descrizione'] ?? '', 0, 100)) ?>...
                                </p>

                                <?php if (empty($prodotto['negozi'])): ?>
                                    <p class="text-danger small">Non disponibile</p>
                                <?php else: ?>
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="action" value="add_cart">
                                        <input type="hidden" name="prodotto" value="<?= $prodotto['id_prodotto'] ?>">

                                        <div class="mb-2">
                                            <select name="negozio" class="form-select form-select-sm" required>
                                                <option value="">Scegli negozio...</option>
                                                <?php foreach ($prodotto['negozi'] as $neg): ?>
                                                    <option value="<?= $neg['negozio'] ?>">
                                                        <?= htmlspecialchars($neg['nome_negozio']) ?> - <?= number_format($neg['prezzo_listino'], 2) ?> (<?= $neg['magazzino'] ?> disp.)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <input type="number" name="quantita" value="1" min="1" class="form-control form-control-sm" style="width: 60px;">
                                            <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                                                <i class="bi bi-cart-plus"></i> Aggiungi
                                            </button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
