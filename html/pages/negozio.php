<?php
session_start();

// Verifica se l'utente è loggato e è un cliente
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_tipo'] !== 'cliente') {
    header('Location: ../index.php?error=access_denied');
    exit;
}

require_once '../config/database.php';

// Genera CSRF token se non esiste
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Recupera messaggio dalla sessione
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

$error = '';
$negozio = null;
$prodotti = [];
$carrello = $_SESSION['carrello'] ?? [];

// Recupera l'ID del negozio dalla query string
$id_negozio = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_negozio <= 0) {
    header('Location: dashboard_cliente.php');
    exit;
}

try {
    $db = getDB();

    // Recupera info negozio
    $stmt = $db->query("SELECT id_negozio, nome_negozio, indirizzo, responsabile FROM negozi.negozi WHERE id_negozio = ?", [$id_negozio]);
    $negozio = $stmt->fetch();

    if (!$negozio) {
        header('Location: dashboard_cliente.php');
        exit;
    }

    // Recupera id_cliente dell'utente loggato
    $stmt = $db->query("SELECT id_cliente, tessera FROM negozi.clienti WHERE utente = ?", [$_SESSION['user_id']]);
    $cliente_data = $stmt->fetch();
    $id_cliente = $cliente_data ? $cliente_data['id_cliente'] : null;
    $id_tessera = $cliente_data ? $cliente_data['tessera'] : null;

    // Recupera punti tessera se disponibile
    $punti_disponibili = 0;
    if ($id_tessera) {
        $stmt = $db->query("SELECT saldo_punti FROM negozi.tessere WHERE id_tessera = ?", [$id_tessera]);
        $tessera_data = $stmt->fetch();
        $punti_disponibili = $tessera_data ? (int)$tessera_data['saldo_punti'] : 0;
    }

} catch (Exception $e) {
    $error = 'Errore nel caricamento: ' . $e->getMessage();
}

// Gestione azioni POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token non valido.');
    }

    // Aggiungi al carrello
    if ($_POST['action'] === 'add_cart') {
        $prodotto_id = (int)($_POST['prodotto'] ?? 0);
        $quantita = (int)($_POST['quantita'] ?? 1);

        if ($prodotto_id > 0 && $quantita > 0) {
            // Inizializza carrello per questo negozio se non esiste
            if (!isset($_SESSION['carrello'][$id_negozio])) {
                $_SESSION['carrello'][$id_negozio] = [];
            }

            // Aggiungi o incrementa quantità
            if (isset($_SESSION['carrello'][$id_negozio][$prodotto_id])) {
                $_SESSION['carrello'][$id_negozio][$prodotto_id] += $quantita;
            } else {
                $_SESSION['carrello'][$id_negozio][$prodotto_id] = $quantita;
            }

            $_SESSION['message'] = "Prodotto aggiunto al carrello!";
        }
        header("Location: negozio.php?id=$id_negozio");
        exit;
    }

    // Rimuovi dal carrello
    if ($_POST['action'] === 'remove_cart') {
        $prodotto_id = (int)($_POST['prodotto'] ?? 0);
        if (isset($_SESSION['carrello'][$id_negozio][$prodotto_id])) {
            unset($_SESSION['carrello'][$id_negozio][$prodotto_id]);
        }
        header("Location: negozio.php?id=$id_negozio");
        exit;
    }

    // Svuota carrello
    if ($_POST['action'] === 'clear_cart') {
        unset($_SESSION['carrello'][$id_negozio]);
        header("Location: negozio.php?id=$id_negozio");
        exit;
    }

    // Conferma acquisto
    if ($_POST['action'] === 'checkout') {
        $usa_punti = isset($_POST['usa_punti']) ? (int)$_POST['usa_punti'] : 0;

        try {
            $db = getDB();
            $carrello_negozio = $_SESSION['carrello'][$id_negozio] ?? [];

            if (empty($carrello_negozio)) {
                throw new RuntimeException('Il carrello è vuoto.');
            }

            if (!$id_cliente) {
                throw new RuntimeException('Dati cliente non trovati.');
            }

            $db->query("BEGIN");

            try {
                // Calcola totale e verifica disponibilità
                $totale = 0;
                $dettagli = [];

                foreach ($carrello_negozio as $prod_id => $qta) {
                    $stmt = $db->query("SELECT ln.prezzo_listino, ln.magazzino, p.nome_prodotto
                                        FROM negozi.listino_negozio ln
                                        JOIN negozi.prodotti p ON ln.prodotto = p.id_prodotto
                                        WHERE ln.negozio = ? AND ln.prodotto = ?", [$id_negozio, $prod_id]);
                    $prod = $stmt->fetch();

                    if (!$prod) {
                        throw new RuntimeException("Prodotto non disponibile in questo negozio.");
                    }

                    if ($prod['magazzino'] < $qta) {
                        throw new RuntimeException("Quantità insufficiente per: " . $prod['nome_prodotto']);
                    }

                    $totale += $prod['prezzo_listino'] * $qta;
                    $dettagli[] = [
                        'prodotto' => $prod_id,
                        'quantita' => $qta,
                        'prezzo' => $prod['prezzo_listino']
                    ];
                }

                // Calcola sconto punti (1 punto = 0.01€)
                $valore_sconto = 0;
                $punti_usati = 0;
                if ($usa_punti > 0 && $id_tessera) {
                    $punti_usati = min($usa_punti, $punti_disponibili, (int)($totale * 100));
                    $valore_sconto = $punti_usati * 0.01;
                }

                $totale_pagato = $totale - $valore_sconto;

                // Crea fattura
                $stmt = $db->query("INSERT INTO negozi.fatture (negozio, cliente, punti_sconto, totale_pagato, valore_scontato)
                                    VALUES (?, ?, ?, ?, ?) RETURNING id_fattura",
                                    [$id_negozio, $id_cliente, $punti_usati, $totale_pagato, $valore_sconto > 0 ? $valore_sconto : null]);
                $fattura_data = $stmt->fetch();
                $id_fattura = $fattura_data['id_fattura'];

                // Inserisci dettagli fattura e aggiorna magazzino
                foreach ($dettagli as $det) {
                    $db->query("INSERT INTO negozi.dettagli_fattura (fattura, prodotto, quantita, prezzo_unita)
                                VALUES (?, ?, ?, ?)",
                                [$id_fattura, $det['prodotto'], $det['quantita'], $det['prezzo']]);

                    $db->query("UPDATE negozi.listino_negozio SET magazzino = magazzino - ?
                                WHERE negozio = ? AND prodotto = ?",
                                [$det['quantita'], $id_negozio, $det['prodotto']]);
                }

                // Aggiorna punti tessera (sottrai usati, aggiungi guadagnati: 1 punto ogni 10€)
                if ($id_tessera) {
                    $punti_guadagnati = (int)($totale_pagato / 10);
                    $db->query("UPDATE negozi.tessere SET saldo_punti = saldo_punti - ? + ? WHERE id_tessera = ?",
                                [$punti_usati, $punti_guadagnati, $id_tessera]);
                }

                $db->query("COMMIT");

                // Svuota carrello
                unset($_SESSION['carrello'][$id_negozio]);

                $_SESSION['message'] = "Acquisto completato! Fattura #$id_fattura - Totale: €" . number_format($totale_pagato, 2);
                header("Location: negozio.php?id=$id_negozio");
                exit;

            } catch (Exception $e) {
                $db->query("ROLLBACK");
                throw $e;
            }

        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Carica prodotti del negozio
if ($negozio) {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT ln.prodotto as id_prodotto, p.nome_prodotto, p.descrizione, p.immagine_url,
                                   ln.prezzo_listino, ln.magazzino
                            FROM negozi.listino_negozio ln
                            JOIN negozi.prodotti p ON ln.prodotto = p.id_prodotto
                            WHERE ln.negozio = ? AND ln.magazzino > 0
                            ORDER BY p.nome_prodotto", [$id_negozio]);
        $prodotti = $stmt->fetchAll();
    } catch (Exception $e) {
        $error = 'Errore nel caricamento prodotti: ' . $e->getMessage();
    }
}

// Carrello del negozio corrente
$carrello_negozio = $_SESSION['carrello'][$id_negozio] ?? [];

// Calcola totale carrello
$totale_carrello = 0;
$prodotti_carrello = [];
if (!empty($carrello_negozio) && $negozio) {
    try {
        $db = getDB();
        foreach ($carrello_negozio as $prod_id => $qta) {
            $stmt = $db->query("SELECT p.nome_prodotto, ln.prezzo_listino
                                FROM negozi.listino_negozio ln
                                JOIN negozi.prodotti p ON ln.prodotto = p.id_prodotto
                                WHERE ln.negozio = ? AND ln.prodotto = ?", [$id_negozio, $prod_id]);
            $prod = $stmt->fetch();
            if ($prod) {
                $subtotale = $prod['prezzo_listino'] * $qta;
                $totale_carrello += $subtotale;
                $prodotti_carrello[] = [
                    'id' => $prod_id,
                    'nome' => $prod['nome_prodotto'],
                    'prezzo' => $prod['prezzo_listino'],
                    'quantita' => $qta,
                    'subtotale' => $subtotale
                ];
            }
        }
    } catch (Exception $e) {
        // Ignora errori nel calcolo carrello
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($negozio['nome_negozio'] ?? 'Negozio') ?> - Retro Gaming Store</title>
    <link rel="icon" type="image/x-icon" href="../images/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
        }
        .navbar-negozio {
            background: linear-gradient(135deg, #e94560 0%, #ff6b6b 100%);
        }
        .product-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
        .product-img {
            height: 150px;
            object-fit: cover;
            width: 100%;
            background: #f8f9fa;
        }
        .product-img-placeholder {
            height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            color: #ccc;
            font-size: 3rem;
        }
        .price-tag {
            font-size: 1.5rem;
            font-weight: bold;
            color: #e94560;
        }
        .cart-sidebar {
            position: fixed;
            right: 0;
            top: 56px;
            width: 350px;
            height: calc(100vh - 56px);
            background: white;
            box-shadow: -5px 0 20px rgba(0,0,0,0.2);
            overflow-y: auto;
            z-index: 1000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }
        .cart-sidebar.open {
            transform: translateX(0);
        }
        .cart-toggle {
            position: fixed;
            right: 20px;
            bottom: 20px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e94560 0%, #ff6b6b 100%);
            color: white;
            border: none;
            box-shadow: 0 5px 20px rgba(233, 69, 96, 0.4);
            z-index: 1001;
            font-size: 1.5rem;
        }
        .cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #fff;
            color: #e94560;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .stock-badge {
            font-size: 0.75rem;
        }
        .punti-info {
            background: linear-gradient(135deg, #ffd700 0%, #ffaa00 100%);
            color: #333;
            padding: 10px 15px;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-negozio">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard_cliente.php">
                <i class="bi bi-arrow-left"></i> Torna ai Negozi
            </a>
            <span class="navbar-text text-white">
                <i class="bi bi-shop"></i> <?= htmlspecialchars($negozio['nome_negozio']) ?>
            </span>
            <div class="navbar-nav ms-auto">
                <span class="nav-link text-white">
                    <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['user_nome']) ?>
                </span>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid py-4" style="padding-right: 380px;">
        <!-- Header negozio -->
        <div class="row mb-4">
            <div class="col">
                <div class="bg-white rounded-3 p-4 shadow">
                    <h2 class="mb-2"><?= htmlspecialchars($negozio['nome_negozio']) ?></h2>
                    <p class="text-muted mb-2">
                        <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($negozio['indirizzo']) ?>
                    </p>
                    <?php if ($punti_disponibili > 0): ?>
                    <div class="punti-info d-inline-block">
                        <i class="bi bi-star-fill"></i> Hai <strong><?= $punti_disponibili ?></strong> punti fedeltà disponibili!
                    </div>
                    <?php endif; ?>
                </div>
            </div>
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

        <!-- Prodotti -->
        <h4 class="text-white mb-3"><i class="bi bi-controller"></i> Prodotti Disponibili</h4>

        <?php if (empty($prodotti)): ?>
            <div class="text-center text-white py-5">
                <i class="bi bi-inbox" style="font-size: 4rem; opacity: 0.5;"></i>
                <p class="mt-3">Nessun prodotto disponibile in questo negozio.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($prodotti as $prodotto): ?>
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <div class="product-card h-100">
                            <?php if (!empty($prodotto['immagine_url'])): ?>
                                <img src="..<?= htmlspecialchars($prodotto['immagine_url']) ?>"
                                     alt="<?= htmlspecialchars($prodotto['nome_prodotto']) ?>"
                                     class="product-img"
                                     onerror="this.outerHTML='<div class=\'product-img-placeholder\'><i class=\'bi bi-controller\'></i></div>'">
                            <?php else: ?>
                                <div class="product-img-placeholder">
                                    <i class="bi bi-controller"></i>
                                </div>
                            <?php endif; ?>

                            <div class="card-body p-3">
                                <h6 class="card-title mb-1"><?= htmlspecialchars($prodotto['nome_prodotto']) ?></h6>
                                <p class="card-text small text-muted mb-2" style="height: 40px; overflow: hidden;">
                                    <?= htmlspecialchars(substr($prodotto['descrizione'] ?? '', 0, 80)) ?>...
                                </p>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="price-tag">€<?= number_format($prodotto['prezzo_listino'], 2) ?></span>
                                    <span class="badge bg-<?= $prodotto['magazzino'] > 5 ? 'success' : ($prodotto['magazzino'] > 0 ? 'warning' : 'danger') ?> stock-badge">
                                        <?= $prodotto['magazzino'] ?> disponibili
                                    </span>
                                </div>
                                <form method="POST" class="d-flex gap-2">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="action" value="add_cart">
                                    <input type="hidden" name="prodotto" value="<?= $prodotto['id_prodotto'] ?>">
                                    <input type="number" name="quantita" value="1" min="1" max="<?= $prodotto['magazzino'] ?>"
                                           class="form-control form-control-sm" style="width: 60px;">
                                    <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
                                        <i class="bi bi-cart-plus"></i> Aggiungi
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Cart Sidebar -->
    <div class="cart-sidebar" id="cartSidebar">
        <div class="p-3 border-bottom bg-light">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-cart3"></i> Carrello</h5>
                <button class="btn btn-sm btn-outline-secondary" onclick="toggleCart()">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>

        <div class="p-3">
            <?php if (empty($prodotti_carrello)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-cart" style="font-size: 3rem;"></i>
                    <p class="mt-2">Il carrello è vuoto</p>
                </div>
            <?php else: ?>
                <?php foreach ($prodotti_carrello as $item): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <div class="flex-grow-1">
                            <strong class="d-block"><?= htmlspecialchars($item['nome']) ?></strong>
                            <small class="text-muted">
                                <?= $item['quantita'] ?> x €<?= number_format($item['prezzo'], 2) ?>
                            </small>
                        </div>
                        <div class="text-end">
                            <strong>€<?= number_format($item['subtotale'], 2) ?></strong>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action" value="remove_cart">
                                <input type="hidden" name="prodotto" value="<?= $item['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-link text-danger p-0 ms-2">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="border-top pt-3">
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Totale:</strong>
                        <strong class="text-primary fs-4">€<?= number_format($totale_carrello, 2) ?></strong>
                    </div>

                    <?php if ($punti_disponibili > 0): ?>
                    <div class="mb-3">
                        <label class="form-label small">Usa punti fedeltà (max <?= min($punti_disponibili, (int)($totale_carrello * 100)) ?>)</label>
                        <input type="number" class="form-control form-control-sm" id="usa_punti"
                               min="0" max="<?= min($punti_disponibili, (int)($totale_carrello * 100)) ?>" value="0"
                               onchange="calcolaSconto()">
                        <small class="text-muted">Risparmio: €<span id="risparmio">0.00</span></small>
                    </div>
                    <?php endif; ?>

                    <form method="POST" id="checkoutForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="checkout">
                        <input type="hidden" name="usa_punti" id="usa_punti_hidden" value="0">
                        <button type="submit" class="btn btn-success w-100 mb-2">
                            <i class="bi bi-credit-card"></i> Completa Acquisto
                        </button>
                    </form>

                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="clear_cart">
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="bi bi-trash"></i> Svuota Carrello
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Cart Toggle Button -->
    <button class="cart-toggle" onclick="toggleCart()">
        <i class="bi bi-cart3"></i>
        <?php if (!empty($prodotti_carrello)): ?>
            <span class="cart-badge"><?= array_sum($carrello_negozio) ?></span>
        <?php endif; ?>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleCart() {
            document.getElementById('cartSidebar').classList.toggle('open');
        }

        function calcolaSconto() {
            const punti = parseInt(document.getElementById('usa_punti').value) || 0;
            const risparmio = (punti * 0.01).toFixed(2);
            document.getElementById('risparmio').textContent = risparmio;
            document.getElementById('usa_punti_hidden').value = punti;
        }

        // Apri carrello automaticamente se ci sono prodotti
        <?php if (!empty($prodotti_carrello)): ?>
        document.getElementById('cartSidebar').classList.add('open');
        <?php endif; ?>
    </script>
</body>
</html>
