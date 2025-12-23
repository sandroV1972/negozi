<?php
require_once '../config/database.php';

// Test della connessione
$db = getDB();
$test_result = $db->testConnection();

// Test query aggiuntive se la connessione funziona
$additional_tests = [];
if ($test_result['success']) {
    try {
        // Test creazione e drop di una tabella temporanea
        $db->query("CREATE TEMPORARY TABLE test_temp (id SERIAL, nome VARCHAR(50))");
        $db->query("INSERT INTO test_temp (nome) VALUES ('Test connessione')");
        $result = $db->query("SELECT * FROM test_temp")->fetch();
        $additional_tests[] = [
            'name' => 'Test scrittura/lettura',
            'success' => true,
            'message' => 'Inserimento e lettura dati: OK'
        ];
    } catch (Exception $e) {
        $additional_tests[] = [
            'name' => 'Test scrittura/lettura',
            'success' => false,
            'message' => 'Errore: ' . $e->getMessage()
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Database - Negozi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
               Retrogame Store
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../index.php">← Torna alla Home</a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        <h4> Test Connessione Database PostgreSQL</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($test_result['success']): ?>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Informazioni Database:</h5>
                                    <ul class="list-group">
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span><i class="bi bi-server"></i> Host:</span>
                                            <code><?= DB_HOST ?></code>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span><i class="bi bi-plug"></i> Porta:</span>
                                            <code><?= DB_PORT ?></code>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span><i class="bi bi-database"></i> Database:</span>
                                            <code><?= $test_result['database'] ?></code>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span><i class="bi bi-person"></i> Utente:</span>
                                            <code><?= $test_result['user'] ?></code>
                                        </li>
                                    </ul>
                                </div>
                                
                                <div class="col-md-6">
                                    <h5>Versione PostgreSQL:</h5>
                                    <div class="bg-light p-3 rounded">
                                        <small><code><?= htmlspecialchars($test_result['version']) ?></code></small>
                                    </div>
                                    
                                    <?php if (!empty($additional_tests)): ?>
                                        <h5 class="mt-3">Test Aggiuntivi:</h5>
                                        <?php foreach ($additional_tests as $test): ?>
                                            <div class="alert alert-<?= $test['success'] ? 'success' : 'warning' ?> py-2">
                                                <i class="bi bi-<?= $test['success'] ? 'check' : 'exclamation-triangle' ?>"></i>
                                                <strong><?= $test['name'] ?>:</strong> <?= $test['message'] ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-x-circle"></i> 
                                <strong>Errore connessione!</strong> <?= $test_result['message'] ?>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Configurazione attuale:</h5>
                                    <ul class="list-group">
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Host:</span>
                                            <code><?= DB_HOST ?></code>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Porta:</span>
                                            <code><?= DB_PORT ?></code>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Database:</span>
                                            <code><?= DB_NAME ?></code>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Utente:</span>
                                            <code><?= DB_USER ?></code>
                                        </li>
                                    </ul>
                                </div>
                                
                                <div class="col-md-6">
                                    <h5>Risoluzione problemi:</h5>
                                    <ol>
                                        <li><strong>PostgreSQL è in esecuzione?</strong><br>
                                            <code>sudo systemctl status postgresql</code></li>
                                        <li><strong>Il database esiste?</strong><br>
                                            <code>sudo -u postgres psql -l</code></li>
                                        <li><strong>Crea il database:</strong><br>
                                            <code>sudo -u postgres createdb <?= DB_NAME ?></code></li>
                                        <li><strong>Password corretta?</strong><br>
                                            Modifica <code>html/config/database.php</code></li>
                                        <li><strong>Permessi utente?</strong><br>
                                            <code>sudo -u postgres psql</code></li>
                                    </ol>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-4 text-center">
                            <a href="../index.php" class="btn btn-primary">
                                <i class="bi bi-house"></i> Torna alla Home
                            </a>
                            <button onclick="location.reload()" class="btn btn-outline-primary">
                                <i class="bi bi-arrow-clockwise"></i> Riprova Test
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>