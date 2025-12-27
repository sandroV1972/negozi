<?php
session_start();

// Verifica se l'utente è loggato e è un cliente
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_tipo'] !== 'cliente') {
    header('Location: ../index.php?error=access_denied');
    exit;
}

require_once '../config/database.php';

// Carica lista negozi
$negozi = [];
try {
    $db = getDB();
    $stmt = $db->query("SELECT n.id_negozio, n.nome_negozio, n.responsabile, n.indirizzo
                        FROM negozi.negozi n
                        ORDER BY n.nome_negozio ASC");
    $negozi = $stmt->fetchAll();
} catch (Exception $e) {
    $error = 'Errore nel caricamento dei negozi: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retro Gaming Store - I Nostri Negozi</title>
    <link rel="icon" type="image/x-icon" href="../images/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
        }
        .hero-section {
            padding: 60px 0 40px;
            text-align: center;
        }
        .hero-logo {
            max-width: 200px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            margin-bottom: 20px;
        }
        .hero-title {
            color: #fff;
            font-size: 2.5rem;
            font-weight: bold;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.5);
            margin-bottom: 10px;
        }
        .hero-subtitle {
            color: #e94560;
            font-size: 1.2rem;
            letter-spacing: 3px;
            text-transform: uppercase;
        }
        .negozio-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }
        .negozio-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(233, 69, 96, 0.4);
        }
        .negozio-header {
            background: linear-gradient(135deg, #e94560 0%, #ff6b6b 100%);
            color: white;
            padding: 20px;
        }
        .negozio-header h5 {
            margin: 0;
            font-weight: bold;
        }
        .negozio-body {
            padding: 20px;
        }
        .negozio-info {
            display: flex;
            align-items: flex-start;
            margin-bottom: 10px;
            color: #333;
        }
        .negozio-info i {
            color: #e94560;
            margin-right: 10px;
            font-size: 1.1rem;
            min-width: 20px;
        }
        .btn-entra {
            background: linear-gradient(135deg, #e94560 0%, #ff6b6b 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 10px 25px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        .btn-entra:hover {
            background: linear-gradient(135deg, #ff6b6b 0%, #e94560 100%);
            color: white;
            transform: scale(1.05);
        }
        .user-nav {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        .user-nav .dropdown-toggle {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }
        .user-nav .dropdown-toggle:hover {
            background: rgba(255, 255, 255, 0.25);
        }
        .section-title {
            color: #fff;
            text-align: center;
            margin-bottom: 40px;
            font-size: 1.8rem;
        }
        .section-title span {
            color: #e94560;
        }
    </style>
</head>
<body>
    <!-- User Navigation -->
    <div class="user-nav dropdown">
        <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle"></i>
            <?= htmlspecialchars($_SESSION['user_nome']) ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><span class="dropdown-item-text text-muted">
                <small><?= htmlspecialchars($_SESSION['user_email']) ?></small>
            </span></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="profilo_cliente.php">
                <i class="bi bi-person-gear"></i> Il mio profilo
            </a></li>
            <li><a class="dropdown-item text-danger" href="../logout.php">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a></li>
        </ul>
    </div>

    <!-- Hero Section con Logo -->
    <div class="hero-section">
        <img src="../images/logo.jpg" alt="Retro Gaming Store" class="hero-logo">
        <h1 class="hero-title">Retro Gaming Store</h1>
        <p class="hero-subtitle">La catena del retrogaming italiano</p>
    </div>

    <!-- Lista Negozi -->
    <div class="container pb-5">
        <h2 class="section-title"><i class="bi bi-shop"></i> I <span>Nostri Negozi</span></h2>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($negozi)): ?>
            <div class="text-center text-white py-5">
                <i class="bi bi-shop" style="font-size: 4rem; opacity: 0.5;"></i>
                <p class="mt-3">Nessun negozio disponibile al momento.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($negozi as $negozio): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="negozio-card h-100">
                            <div class="negozio-header">
                                <h5><i class="bi bi-controller"></i> <?= htmlspecialchars($negozio['nome_negozio']) ?></h5>
                            </div>
                            <div class="negozio-body d-flex flex-column">
                                <div class="negozio-info">
                                    <i class="bi bi-geo-alt-fill"></i>
                                    <span><?= htmlspecialchars($negozio['indirizzo']) ?></span>
                                </div>
                                <div class="negozio-info">
                                    <i class="bi bi-person-badge"></i>
                                    <span>Responsabile: <?= htmlspecialchars($negozio['responsabile']) ?></span>
                                </div>
                                <div class="mt-auto pt-3 text-center">
                                    <a href="negozio.php?id=<?= $negozio['id_negozio'] ?>" class="btn btn-entra">
                                        <i class="bi bi-arrow-right-circle"></i> Entra nel Negozio
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="text-center py-4" style="color: rgba(255,255,255,0.5);">
        <small>
            <i class="bi bi-controller"></i> Retro Gaming Store - La passione per il retrogaming
        </small>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
