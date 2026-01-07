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
    <!-- Retro Arcade Theme -->
    <link href="../css/retro-arcade.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar Semplificata -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-warning">
        <div class="container-fluid">
            <!-- Solo il titolo a sinistra -->
            <a class="navbar-brand" href="dashboard_manager.php">
                Dashboard Manager
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
        <div class="row">
            <div class="col-12">
                
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

                <!-- Views -->
                <div class="row mt-4">
                    <div class="col-12">
                        <h5><i class="bi bi-eye"></i> Visualizzazioni</h5>
                        <div class="btn-group flex-wrap" role="group">
                            <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalClientiTop">
                                <i class="bi bi-star"></i> Clienti 300 punti
                            </button>
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

    <!-- Modal Prodotti per Negozio -->
    <div class="modal fade" id="modalProdottiNegozio" tabindex="-1" aria-labelledby="modalProdottiNegozioLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalProdottiNegozioLabel">
                        <i class="bi bi-box-seam"></i> Prodotti per Negozio
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Contenuto View -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ordini Fornitori -->
    <div class="modal fade" id="modalOrdiniFornitori" tabindex="-1" aria-labelledby="modalOrdiniFornitoriLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalOrdiniFornitoriLabel">
                        <i class="bi bi-card-list"></i> Ordini Fornitori
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Contenuto View -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Clienti Top 300 punti -->
    <div class="modal fade" id="modalClientiTop" tabindex="-1" aria-labelledby="modalClientiTopLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalClientiTopLabel">
                        Clienti con +300 punti
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalClientiTopBody">
                    
                    <!-- Contenuto caricato via fetch -->
                    <div class="text-center py-5">
                        <div class="spinner-border text-info" role="status">
                            <span class="visually-hidden">Caricamento...</span>
                        </div>
                        <p class="mt-2 text-muted">Caricamento dati...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Fornitori Attivi -->
    <div class="modal fade" id="modalFornitoriAttivi" tabindex="-1" aria-labelledby="modalFornitoriAttiviLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="modalFornitoriAttiviLabel">
                        <i class="bi bi-truck-loading"></i> Fornitori Attivi
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Contenuto View -->
                     Contenuto da implementare
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Carica dati clienti 300+ punti quando si apre la modale
        document.getElementById('modalClientiTop').addEventListener('show.bs.modal', function () {
            const body = document.getElementById('modalClientiTopBody');

            // Mostra spinner
            body.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-info" role="status">
                        <span class="visually-hidden">Caricamento...</span>
                    </div>
                    <p class="mt-2 text-muted">Caricamento dati...</p>
                </div>
            `;

            // Fetch dati dall'API
            fetch('api/clienti_top.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        let html = `
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Nome Cognome</th>
                                            <th>Punti</th>
                                            <th>Negozio</th>
                                            <th>Data emissione</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;
                        data.data.forEach(cliente => {
                            html += `
                                <tr>
                                    <td>${cliente.nome} ${cliente.cognome || ''}</td>
                                    <td><span class="badge bg-success">${cliente.saldo_punti} punti</span></td>
                                    <td>${cliente.nome_negozio || '-'}</td>
                                    <td>${cliente.data_richiesta ? new Date(cliente.data_richiesta).toLocaleDateString() : '-'}</td>
                                </tr>

                            `;
                        });
                        html += `
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-muted mt-2">Totale: ${data.count} clienti con 300+ punti</p>
                        `;
                        body.innerHTML = html;
                    } else if (data.success && data.data.length === 0) {
                        body.innerHTML = `
                            <div class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                <p class="mt-3 text-muted">Nessun cliente con 300+ punti trovato.</p>
                            </div>
                        `;
                    } else {
                        body.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i> Attenzione: ${data.error || 'Errore sconosciuto'}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    body.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> Errore di connessione: ${error.message}
                        </div>
                    `;
                });
        });
    </script>
</body>
</html>