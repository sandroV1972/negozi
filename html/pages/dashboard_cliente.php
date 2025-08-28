<?php
session_start();

// Verifica se l'utente è loggato e è un cliente
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_tipo'] !== 'cliente') {
    header('Location: ../index.php?error=access_denied');
    exit;
}

require_once '../config/database.php';
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Cliente - Sistema Negozi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard_cliente.php">
                <i class="bi bi-person-circle"></i> Area Cliente
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="catalogo_prodotti.php">
                            <i class="bi bi-shop-window"></i> Catalogo Prodotti
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="miei_ordini.php">
                            <i class="bi bi-bag-check"></i> I Miei Ordini
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="tessera_fedelta.php">
                            <i class="bi bi-credit-card-2-front"></i> Tessera Fedeltà
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="negozi_vicini.php">
                            <i class="bi bi-geo-alt"></i> Negozi
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> 
                            <?= htmlspecialchars($_SESSION['user_nome'] . ' ' . $_SESSION['user_cognome']) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profilo_cliente.php">
                                <i class="bi bi-person-gear"></i> Il mio profilo
                            </a></li>
                            <li><a class="dropdown-item" href="cambia_password_cliente.php">
                                <i class="bi bi-key"></i> Cambia password
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i>
                    <strong>Benvenuto nella tua Area Cliente!</strong> 
                    Esplora i prodotti e gestisci i tuoi ordini.
                </div>
                
                <!-- Cards principali -->
                <div class="row">
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card text-white bg-primary h-100">
                            <div class="card-body text-center d-flex flex-column">
                                <i class="bi bi-shop-window" style="font-size: 3rem;"></i>
                                <h5 class="card-title mt-3">Catalogo</h5>
                                <p class="card-text flex-grow-1">Sfoglia tutti i prodotti disponibili</p>
                                <a href="catalogo_prodotti.php" class="btn btn-light mt-auto">
                                    <i class="bi bi-arrow-right"></i> Esplora
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card text-white bg-success h-100">
                            <div class="card-body text-center d-flex flex-column">
                                <i class="bi bi-bag-check" style="font-size: 3rem;"></i>
                                <h5 class="card-title mt-3">I Miei Ordini</h5>
                                <p class="card-text flex-grow-1">Visualizza storico degli acquisti</p>
                                <a href="miei_ordini.php" class="btn btn-light mt-auto">
                                    <i class="bi bi-arrow-right"></i> Visualizza
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card text-white bg-info h-100">
                            <div class="card-body text-center d-flex flex-column">
                                <i class="bi bi-credit-card-2-front" style="font-size: 3rem;"></i>
                                <h5 class="card-title mt-3">Tessera Fedeltà</h5>
                                <p class="card-text flex-grow-1">Punti e sconti disponibili</p>
                                <a href="tessera_fedelta.php" class="btn btn-light mt-auto">
                                    <i class="bi bi-arrow-right"></i> Gestisci
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card text-white bg-warning h-100">
                            <div class="card-body text-center d-flex flex-column">
                                <i class="bi bi-geo-alt" style="font-size: 3rem;"></i>
                                <h5 class="card-title mt-3">Negozi</h5>
                                <p class="card-text flex-grow-1">Trova il negozio più vicino</p>
                                <a href="negozi_vicini.php" class="btn btn-light mt-auto">
                                    <i class="bi bi-arrow-right"></i> Trova
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Statistiche personali -->
                <div class="row mt-4">
                    <div class="col-12">
                        <h5><i class="bi bi-graph-up"></i> Il Tuo Riepilogo</h5>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <h3 class="text-primary">--</h3>
                                        <small class="text-muted">Ordini Totali</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-success">
                                    <div class="card-body text-center">
                                        <h3 class="text-success">--</h3>
                                        <small class="text-muted">Punti Fedeltà</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-info">
                                    <div class="card-body text-center">
                                        <h3 class="text-info">--€</h3>
                                        <small class="text-muted">Totale Speso</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-warning">
                                    <div class="card-body text-center">
                                        <h3 class="text-warning">--€</h3>
                                        <small class="text-muted">Sconti Ottenuti</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Informazioni di debug -->
                <div class="mt-5">
                    <div class="card bg-light">
                        <div class="card-header">
                            <h6><i class="bi bi-info-circle"></i> Informazioni Account (Debug)</h6>
                        </div>
                        <div class="card-body">
                            <small>
                                <strong>ID:</strong> <?= $_SESSION['user_id'] ?><br>
                                <strong>Email:</strong> <?= $_SESSION['user_email'] ?><br>
                                <strong>Tipo:</strong> <?= $_SESSION['user_tipo'] ?><br>
                                <strong>Nome completo:</strong> <?= $_SESSION['user_nome'] . ' ' . $_SESSION['user_cognome'] ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>