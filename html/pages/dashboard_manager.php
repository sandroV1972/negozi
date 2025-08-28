<?php
session_start();

// Verifica se l'utente è loggato e è un manager
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_tipo'] !== 'manager') {
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
    <title>Dashboard Manager - Sistema Negozi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar Semplificata -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-warning">
        <div class="container-fluid">
            <!-- Solo il titolo a sinistra -->
            <a class="navbar-brand" href="dashboard_manager.php">
                <i class="bi bi-briefcase"></i> Dashboard Manager
            </a>
            
            <!-- Solo dropdown utente a destra -->
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
                        <li><a class="dropdown-item" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i>
                    <strong>Benvenuto nella Dashboard Manager!</strong> 
                    Gestisci clienti, negozi, prodotti e fornitori.
                </div>
                
                <!-- Cards principali -->
                <div class="row">
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card text-white bg-primary h-100">
                            <div class="card-body text-center d-flex flex-column">
                                <i class="bi bi-people" style="font-size: 3rem;"></i>
                                <h5 class="card-title mt-3">Clienti</h5>
                                <p class="card-text flex-grow-1">Gestione anagrafica clienti e tessere fedeltà</p>
                                <a href="gestione_clienti.php" class="btn btn-light mt-auto">
                                    <i class="bi bi-arrow-right"></i> Gestisci
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card text-white bg-success h-100">
                            <div class="card-body text-center d-flex flex-column">
                                <i class="bi bi-shop" style="font-size: 3rem;"></i>
                                <h5 class="card-title mt-3">Negozi</h5>
                                <p class="card-text flex-grow-1">Gestione punti vendita e responsabili</p>
                                <a href="gestione_negozi.php" class="btn btn-light mt-auto">
                                    <i class="bi bi-arrow-right"></i> Gestisci
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card text-white bg-info h-100">
                            <div class="card-body text-center d-flex flex-column">
                                <i class="bi bi-box" style="font-size: 3rem;"></i>
                                <h5 class="card-title mt-3">Prodotti</h5>
                                <p class="card-text flex-grow-1">Catalogo prodotti e prezzi per negozio</p>
                                <a href="gestione_prodotti.php" class="btn btn-light mt-auto">
                                    <i class="bi bi-arrow-right"></i> Gestisci
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card text-white bg-warning h-100">
                            <div class="card-body text-center d-flex flex-column">
                                <i class="bi bi-truck" style="font-size: 3rem;"></i>
                                <h5 class="card-title mt-3">Fornitori</h5>
                                <p class="card-text flex-grow-1">Gestione fornitori e ordini</p>
                                <a href="gestione_fornitori.php" class="btn btn-light mt-auto">
                                    <i class="bi bi-arrow-right"></i> Gestisci
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Statistiche rapide -->
                <div class="row mt-4">
                    <div class="col-12">
                        <h5><i class="bi bi-graph-up"></i> Statistiche Rapide</h5>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <h3 class="text-primary">--</h3>
                                        <small class="text-muted">Clienti Totali</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-success">
                                    <div class="card-body text-center">
                                        <h3 class="text-success">--</h3>
                                        <small class="text-muted">Negozi Attivi</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-info">
                                    <div class="card-body text-center">
                                        <h3 class="text-info">--</h3>
                                        <small class="text-muted">Prodotti</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-warning">
                                    <div class="card-body text-center">
                                        <h3 class="text-warning">--</h3>
                                        <small class="text-muted">Fornitori</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Azioni rapide -->
                <div class="row mt-4">
                    <div class="col-12">
                        <h5><i class="bi bi-lightning"></i> Azioni Rapide</h5>
                        <div class="btn-group flex-wrap" role="group">
                            <a href="gestione_clienti.php?action=new" class="btn btn-outline-primary">
                                <i class="bi bi-person-plus"></i> Nuovo Cliente
                            </a>
                            <a href="gestione_negozi.php?action=new" class="btn btn-outline-success">
                                <i class="bi bi-shop"></i> Nuovo Negozio
                            </a>
                            <a href="gestione_prodotti.php?action=new" class="btn btn-outline-info">
                                <i class="bi bi-box"></i> Nuovo Prodotto
                            </a>
                            <a href="gestione_fornitori.php?action=new" class="btn btn-outline-warning">
                                <i class="bi bi-truck"></i> Nuovo Fornitore
                            </a>
                            <a href="test_db.php" class="btn btn-outline-secondary">
                                <i class="bi bi-database-check"></i> Test Database
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>