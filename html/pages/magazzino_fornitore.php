<?php
session_start();

// Verifica se l'utente è loggato ed è un manager
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_tipo'] !== 'manager') {
    header('Location: ../index.php?error=access_denied');
    exit;
}

require_once '../config/database.php';

$error = '';
$piva = trim($_GET['piva'] ?? '');
$fornitore = null;
$prodotti = [];

if (empty($piva)) {
    header('Location: gestione_fornitori.php');
    exit;
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
            <a href="gestione_fornitori.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Torna ai Fornitori
            </a>
        </div>

        <!-- Messaggi errore -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Info Fornitore -->
        <?php if ($fornitore): ?>
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informazioni Fornitore</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Indirizzo:</strong><br>
                               <?= $fornitore['indirizzo'] ? htmlspecialchars($fornitore['indirizzo']) : '<span class="text-muted">Non specificato</span>' ?>
                            </p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Email:</strong><br>
                               <?php if ($fornitore['email']): ?>
                                   <a href="mailto:<?= htmlspecialchars($fornitore['email']) ?>">
                                       <i class="bi bi-envelope"></i> <?= htmlspecialchars($fornitore['email']) ?>
                                   </a>
                               <?php else: ?>
                                   <span class="text-muted">Non specificata</span>
                               <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Telefono:</strong><br>
                               <?php if ($fornitore['telefono']): ?>
                                   <a href="tel:<?= htmlspecialchars($fornitore['telefono']) ?>">
                                       <i class="bi bi-telephone"></i> <?= htmlspecialchars($fornitore['telefono']) ?>
                                   </a>
                               <?php else: ?>
                                   <span class="text-muted">Non specificato</span>
                               <?php endif; ?>
                            </p>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
