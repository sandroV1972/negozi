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

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
$error = '';

// Recupera dati cliente
$id_cliente = null;
$id_tessera = null;
$punti_disponibili = 0;
$cliente = null;

try {
    $db = getDB();
    $stmt = $db->query("SELECT c.id_cliente, c.tessera, c.nome, c.cognome, c.cf, c.telefono
                        FROM negozi.clienti c
                        WHERE c.utente = ?", [$_SESSION['user_id']]);
    $cliente = $stmt->fetch();
    if ($cliente) {
        $id_cliente = $cliente['id_cliente'];
        $id_tessera = $cliente['tessera'];
        if ($id_tessera) {
            $stmt = $db->query("SELECT saldo_punti, data_richiesta FROM negozi.tessere WHERE id_tessera = ?", [$id_tessera]);
            $tessera = $stmt->fetch();
            $punti_disponibili = $tessera ? (int)$tessera['saldo_punti'] : 0;
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Gestione cambio password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cambia_password') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token non valido.');
    }

    $password_attuale = $_POST['password_attuale'] ?? '';
    $nuova_password = $_POST['nuova_password'] ?? '';
    $conferma_password = $_POST['conferma_password'] ?? '';

    if (empty($password_attuale) || empty($nuova_password) || empty($conferma_password)) {
        $error = "Tutti i campi sono obbligatori.";
    } elseif ($nuova_password !== $conferma_password) {
        $error = "Le nuove password non coincidono.";
    } elseif (strlen($nuova_password) < 8) {
        $error = "La nuova password deve essere di almeno 8 caratteri.";
    } else {
        try {
            $db = getDB();
            $stmt = $db->query("SELECT password FROM auth.utenti WHERE id_utente = ?", [$_SESSION['user_id']]);
            $utente = $stmt->fetch();

            if ($utente && password_verify($password_attuale, $utente['password'])) {
                $nuovo_hash = password_hash($nuova_password, PASSWORD_DEFAULT);
                $db->query("UPDATE auth.utenti SET password = ? WHERE id_utente = ?",
                          [$nuovo_hash, $_SESSION['user_id']]);
                $_SESSION['message'] = "Password cambiata con successo!";
                header("Location: profilo_cliente.php");
                exit;
            } else {
                $error = "La password attuale non e' corretta.";
            }
        } catch (Exception $e) {
            $error = "Errore: " . $e->getMessage();
        }
    }
}

// Recupera lista fatture del cliente
$fatture = [];
try {
    $db = getDB();
    $stmt = $db->query("SELECT f.id_fattura, f.data_fattura, f.totale_pagato, f.sconto_percentuale
                        FROM negozi.fatture f
                        WHERE f.cliente = ?
                        ORDER BY f.data_fattura DESC, f.id_fattura DESC",
                        [$id_cliente]);
    $fatture = $stmt->fetchAll();
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Il mio profilo - Retro Gaming Store</title>
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
                <a class="nav-link" href="dashboard_cliente.php">
                    <i class="bi bi-arrow-left"></i> Torna al catalogo
                </a>
                <?php if ($punti_disponibili > 0): ?>
                    <span class="badge bg-warning text-dark ms-2"><?= $punti_disponibili ?> punti</span>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <h2 class="mb-4"><i class="bi bi-person-circle"></i> Il mio profilo</h2>

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

        <div class="row">
            <!-- Colonna sinistra: Dati profilo e cambio password -->
            <div class="col-md-4">
                <!-- Dati profilo -->
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white">
                        <i class="bi bi-person"></i> I miei dati
                    </div>
                    <div class="card-body">
                        <?php if ($cliente): ?>
                            <p><strong>Nome:</strong> <?= htmlspecialchars($cliente['nome'] . ' ' . $cliente['cognome']) ?></p>
                            <p><strong>Codice Fiscale:</strong> <?= htmlspecialchars($cliente['cf']) ?></p>
                            <p><strong>Telefono:</strong> <?= htmlspecialchars($cliente['telefono'] ?? 'Non specificato') ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></p>
                            <hr>
                            <p><strong>Tessera:</strong> <?= $id_tessera ? '#' . $id_tessera : 'Nessuna' ?></p>
                            <p><strong>Punti disponibili:</strong> <span class="badge bg-warning text-dark"><?= $punti_disponibili ?></span></p>
                        <?php else: ?>
                            <p class="text-muted">Dati non disponibili.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Cambio password -->
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <i class="bi bi-key"></i> Cambia password
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="cambia_password">

                            <div class="mb-3">
                                <label class="form-label small">Password attuale</label>
                                <input type="password" name="password_attuale" class="form-control form-control-sm" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">Nuova password</label>
                                <input type="password" name="nuova_password" class="form-control form-control-sm" required minlength="8">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">Conferma nuova password</label>
                                <input type="password" name="conferma_password" class="form-control form-control-sm" required minlength="8">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="bi bi-check"></i> Cambia password
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Colonna destra: Lista fatture -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <i class="bi bi-receipt"></i> Le mie fatture
                    </div>
                    <div class="card-body">
                        <?php if (empty($fatture)): ?>
                            <p class="text-muted text-center">Non hai ancora effettuato acquisti.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Data</th>
                                            <th class="text-end">Totale</th>
                                            <th class="text-center">Sconto</th>
                                            <th class="text-center">Dettagli</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($fatture as $fattura): ?>
                                            <tr>
                                                <td><?= $fattura['id_fattura'] ?></td>
                                                <td><?= date('d/m/Y', strtotime($fattura['data_fattura'])) ?></td>
                                                <td class="text-end"><?= number_format($fattura['totale_pagato'], 2) ?></td>
                                                <td class="text-center">
                                                    <?php if ($fattura['sconto_percentuale'] > 0): ?>
                                                        <span class="badge bg-success"><?= $fattura['sconto_percentuale'] ?>%</span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-outline-primary" type="button"
                                                            data-bs-toggle="collapse"
                                                            data-bs-target="#dettagli-<?= $fattura['id_fattura'] ?>">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr class="collapse" id="dettagli-<?= $fattura['id_fattura'] ?>">
                                                <td colspan="5" class="bg-light">
                                                    <?php
                                                    // Carica dettagli fattura
                                                    $stmt = $db->query("SELECT df.quantita, df.prezzo_unita, p.nome_prodotto
                                                                        FROM negozi.dettagli_fattura df
                                                                        JOIN negozi.prodotti p ON df.prodotto = p.id_prodotto
                                                                        WHERE df.fattura = ?",
                                                                        [$fattura['id_fattura']]);
                                                    $dettagli = $stmt->fetchAll();
                                                    ?>
                                                    <table class="table table-sm mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th>Prodotto</th>
                                                                <th class="text-center">Quantita</th>
                                                                <th class="text-end">Prezzo</th>
                                                                <th class="text-end">Subtotale</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($dettagli as $det): ?>
                                                                <tr>
                                                                    <td><?= htmlspecialchars($det['nome_prodotto']) ?></td>
                                                                    <td class="text-center"><?= $det['quantita'] ?></td>
                                                                    <td class="text-end"><?= number_format($det['prezzo_unita'], 2) ?></td>
                                                                    <td class="text-end"><?= number_format($det['prezzo_unita'] * $det['quantita'], 2) ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
