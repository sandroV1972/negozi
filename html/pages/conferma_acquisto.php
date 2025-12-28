<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_tipo'] !== 'cliente') {
    header('Location: ../index.php?error=access_denied');
    exit;
}

// Verifica che ci siano dati di conferma
if (!isset($_SESSION['conferma_acquisto'])) {
    header('Location: dashboard_cliente.php');
    exit;
}

$conferma = $_SESSION['conferma_acquisto'];
unset($_SESSION['conferma_acquisto']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conferma Acquisto - Retro Gaming Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard_cliente.php">
                <i class="bi bi-shop"></i> Retro Gaming Store
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard_cliente.php">
                    <i class="bi bi-arrow-left"></i> Torna al catalogo
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="text-center mb-4">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
            <h2 class="mt-3">Acquisto completato!</h2>
            <p class="text-muted">Grazie per il tuo acquisto, <?= htmlspecialchars($_SESSION['user_nome']) ?>!</p>
        </div>

        <!-- Riepilogo punti -->
        <div class="row justify-content-center mb-4">
            <div class="col-md-6">
                <div class="card bg-warning text-dark">
                    <div class="card-body text-center">
                        <h5 class="card-title"><i class="bi bi-star-fill"></i> Punti Fedeltà</h5>
                        <p class="mb-1">Punti guadagnati con questo acquisto:</p>
                        <h3 class="mb-2">+<?= $conferma['punti_guadagnati'] ?> punti</h3>
                        <small>Saldo totale punti: <strong><?= $conferma['punti_totali'] ?></strong></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fatture generate -->
        <h4 class="mb-3"><i class="bi bi-receipt"></i> Dettaglio Fatture</h4>

        <?php foreach ($conferma['fatture'] as $fattura): ?>
            <div class="card mb-3">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <span>
                        <i class="bi bi-shop"></i> <?= htmlspecialchars($fattura['nome_negozio']) ?>
                    </span>
                    <span class="badge bg-light text-dark">Fattura #<?= $fattura['id_fattura'] ?></span>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Prodotto</th>
                                <th class="text-center">Quantità</th>
                                <th class="text-end">Prezzo unitario</th>
                                <th class="text-end">Subtotale</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fattura['dettagli'] as $det): ?>
                                <tr>
                                    <td><?= htmlspecialchars($det['nome_prodotto']) ?></td>
                                    <td class="text-center"><?= $det['quantita'] ?></td>
                                    <td class="text-end"><?= number_format($det['prezzo'], 2) ?></td>
                                    <td class="text-end"><?= number_format($det['subtotale'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-secondary">
                                <td colspan="3" class="text-end"><strong>Totale fattura:</strong></td>
                                <td class="text-end"><strong><?= number_format($fattura['totale'], 2) ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Totale generale -->
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-wallet2"></i> Totale pagato</h5>
                    <h4 class="mb-0"><?= number_format($conferma['totale_generale'], 2) ?></h4>
                </div>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="dashboard_cliente.php" class="btn btn-primary btn-lg">
                <i class="bi bi-arrow-left"></i> Continua lo shopping
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
