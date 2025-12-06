<?php
session_start();

// Verifica se l'utente è loggato e è un manager
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_tipo'] !== 'manager') {
    header('Location: ../index.php?error=access_denied');
    exit;
}

require_once '../config/database.php';

$message = '';
$error = '';
$action = $_GET['action'] ?? '';

// Gestione azioni POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $nome = trim($_POST['nome'] ?? '');
    $cognome = trim($_POST['cognome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    
    try {
        $db = Database::getInstance();
        
        if ($_POST['action'] === 'create') {
            if (empty($nome) || empty($cognome) || empty($email)) {
                throw new RuntimeException('Nome, cognome ed email sono obbligatori.');
            }
            
            // Inizia transazione per creare sia utente che cliente
            $db->query("BEGIN");
            
            try {
                // 1. Crea utente in auth.utenti
                $password_hash = password_hash('password123', PASSWORD_DEFAULT);
                $username = strtolower($nome . $cognome);
                
                $stmt = $db->query("INSERT INTO auth.utenti (email, username, password_hash, attivo) VALUES (?, ?, ?, TRUE) RETURNING id_utente", 
                    [$email, $username, $password_hash]);
                $user_data = $stmt->fetch();
                $user_id = $user_data['id_utente'];
                
                // 2. Genera numero tessera fedeltà
                $tessera_numero = 'T' . str_pad($user_id, 6, '0', STR_PAD_LEFT);
                
                // 3. Crea cliente in negozi.clienti
                $db->query("INSERT INTO negozi.clienti (id_utente, nome, cognome, telefono, numero_tessera) VALUES (?, ?, ?, ?, ?)", 
                    [$user_id, $nome, $cognome, $telefono, $tessera_numero]);
                
                // Conferma transazione
                $db->query("COMMIT");
                
                $message = "Cliente creato! Email: $email - Password: password123 - Tessera: $tessera_numero";
                $action = ''; // Torna alla lista
                
            } catch (Exception $e) {
                // Rollback in caso di errore
                $db->query("ROLLBACK");
                throw $e;
            }
        }
        
        if ($_POST['action'] === 'delete') {
            $id_cliente = (int)($_POST['id_cliente'] ?? 0);
            if ($id_cliente <= 0) {
                throw new RuntimeException('ID cliente non valido.');
            }
            
            // Elimina cliente (la view non supporta DELETE, usiamo le tabelle base)
            $db->query("DELETE FROM negozi.clienti WHERE id = ?", [$id_cliente]);
            $message = "Cliente eliminato correttamente.";
        }
        
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'duplicate key') !== false) {
            $error = 'Email già esistente.';
        } else {
            $error = 'Errore: ' . $e->getMessage();
        }
    }
}

// Carica lista clienti dalla view
try {
    $db = Database::getInstance();
    $stmt = $db->query("SELECT id, username, nome, cognome, numero_tessera, saldo_punti FROM negozi.lista_clienti ORDER BY id");
    $clienti = $stmt->fetchAll();
} catch (Exception $e) {
    $error = 'Errore nel caricamento: ' . $e->getMessage();
    $clienti = [];
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Clienti - Sistema Negozi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-warning">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard_manager.php">
                <i class="bi bi-briefcase"></i> Dashboard Manager
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
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard_manager.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Gestione Clienti</li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-people"></i> Gestione Clienti</h2>
            <?php if ($action !== 'new'): ?>
                <a href="?action=new" class="btn btn-primary">
                    <i class="bi bi-person-plus"></i> Nuovo Cliente
                </a>
            <?php endif; ?>
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

        <?php if ($action === 'new'): ?>
            <!-- Form Nuovo Cliente -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="bi bi-person-plus"></i> Nuovo Cliente</h4>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="create">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="nome" class="form-label">Nome</label>
                                            <input type="text" class="form-control" id="nome" name="nome" 
                                                   value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="cognome" class="form-label">Cognome</label>
                                            <input type="text" class="form-control" id="cognome" name="cognome" 
                                                   value="<?= htmlspecialchars($_POST['cognome'] ?? '') ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="telefono" class="form-label">Telefono</label>
                                    <input type="tel" class="form-control" id="telefono" name="telefono" 
                                           value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>">
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>Nota:</strong> La password di default sarà "password123" e verrà generata automaticamente una tessera fedeltà.
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-check-circle"></i> Crea Cliente
                                    </button>
                                    <a href="gestione_clienti.php" class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i> Annulla
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Lista Clienti -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-list"></i> Lista Clienti</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($clienti)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                            <p class="mt-3 text-muted">Nessun cliente trovato nella view negozi.lista_clienti.</p>
                            <a href="?action=new" class="btn btn-primary">
                                <i class="bi bi-person-plus"></i> Crea il primo cliente
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Nome Completo</th>
                                        <th>Tessera</th>
                                        <th>Punti</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($clienti as $cliente): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-secondary"><?= $cliente['id'] ?></span>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($cliente['username']) ?></strong>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($cliente['nome'] . ' ' . $cliente['cognome']) ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?= htmlspecialchars($cliente['numero_tessera']) ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success"><?= $cliente['saldo_punti'] ?> pt</span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" title="Modifica" 
                                                            onclick="modificaCliente(<?= $cliente['id'] ?>)">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" title="Elimina" 
                                                            onclick="eliminaCliente(<?= $cliente['id'] ?>, '<?= htmlspecialchars($cliente['nome'] . ' ' . $cliente['cognome']) ?>')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function modificaCliente(id) {
            // Per ora solo alert, poi implementeremo
            alert('Modifica cliente ID: ' + id);
            // TODO: Implementare edit inline o redirect
        }
        
        function eliminaCliente(id, nome) {
            if (confirm('Sei sicuro di voler eliminare il cliente "' + nome + '"?')) {
                // Crea form nascosto per eliminazione
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id_cliente" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>