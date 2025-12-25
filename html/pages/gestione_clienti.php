<?php
session_start();

// Verifica se l'utente è loggato ed è un manager
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_tipo'] !== 'manager') {
    header('Location: ../index.php?error=access_denied');
    exit;
}

require_once '../config/database.php';

// Genera CSRF token se non esiste
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Recupera messaggio dalla sessione (dopo redirect)
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']); // Rimuovi dopo averlo letto

$error = '';
$action = $_GET['action'] ?? '';

// Gestione azioni POST gestione_clienti.php?action=create update o delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Verifica CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token non valido. Possibile attacco CSRF.');
    }
    $nome = trim($_POST['nome'] ?? '');
    $cf = trim($_POST['cf'] ?? '');
    $cognome = trim($_POST['cognome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    
    try {
        $db = getDB();
        
        // Aggiorna cliente
        if ($_POST['action'] === 'update') {
            if (empty($nome) || empty($cf) || empty($email)) {
                throw new RuntimeException('Nome, CF ed email sono obbligatori.');
            }

            // ID cliente viene dall'URL, non dal POST
            $id_cliente = (int)($_GET['id'] ?? 0);
            if ($id_cliente <= 0) {
                throw new RuntimeException('ID cliente non valido.');
            }

            // Inizia transazione per aggiornare sia utente che cliente
            $db->query("BEGIN");

            try {
                // 1. Aggiorna email in auth.utenti
                $db->query("UPDATE auth.utenti SET email = ?
                            WHERE id_utente = (SELECT utente FROM negozi.clienti WHERE id_cliente = ?)",
                            [$email, $id_cliente]);

                // 2. Aggiorna dati cliente in negozi.clienti
                $db->query("UPDATE negozi.clienti SET nome = ?, cognome = NULLIF(?,''), cf = ?, telefono = ?
                            WHERE id_cliente = ?",
                            [$nome, $cognome, $cf, $telefono, $id_cliente]);

                // Conferma transazione
                $db->query("COMMIT");

                // Redirect per evitare re-submit e pulire $_POST (Pattern PRG: Post-Redirect-Get)
                $_SESSION['message'] = "Cliente modificato correttamente.";
                header('Location: gestione_clienti.php');
                exit;

            } catch (Exception $e) {
                // Rollback in caso di errore
                $db->query("ROLLBACK");
                throw $e;
            }
        }

        if ($_POST['action'] === 'create') {
            if (empty($nome) || empty($cf) || empty($email)) {
                throw new RuntimeException('Nome, CF ed email sono obbligatori.');
            }
            
            // Inizia transazione per creare sia utente che cliente
            $db->query("BEGIN");
            
            try {
                // 1. Crea utente in auth.utenti
                $password_hash = password_hash('password123', PASSWORD_DEFAULT);

                $stmt = $db->query("INSERT INTO auth.utenti (email, password, attivo) VALUES (?, ?, TRUE) RETURNING id_utente",
                    [$email, $password_hash]);
                $user_data = $stmt->fetch();
                $user_id = $user_data['id_utente'];

                // 2. Crea cliente in negozi.clienti
                $db->query("INSERT INTO negozi.clienti (utente, cf, nome, cognome) VALUES (?, ?, ?, NULLIF(?,''))",
                    [$user_id, $cf, $nome, $cognome]);
                
                // Conferma transazione
                $db->query("COMMIT");

                // Redirect per evitare re-submit e pulire $_POST (Pattern PRG: Post-Redirect-Get)
                $_SESSION['message'] = "Cliente creato! Email: $email - Password: password123";
                header('Location: gestione_clienti.php');
                exit;
                
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

            // Redirect per evitare re-submit e pulire $_POST
            $_SESSION['message'] = "Cliente eliminato correttamente.";
            header('Location: gestione_clienti.php');
            exit;
        }
        
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'duplicate key') !== false) {
            $error = 'Email già esistente.';
        } else {
            $error = 'Errore: ' . $e->getMessage();
        }
    }
}

// Carica dati del cliente se siamo in modalità modifica (GET, non POST!)
$cliente = [];
if ($action === 'modifica' && isset($_GET['id'])) {
    $id_cliente = (int)$_GET['id'];
    try {
        $db = getDB();
        $stmt = $db->query("SELECT c.id_cliente, c.nome, c.cognome, c.cf, u.email, c.telefono
                            FROM negozi.clienti c
                            JOIN auth.utenti u ON c.utente = u.id_utente
                            WHERE c.id_cliente = ?", [$id_cliente]);
        $cliente = $stmt->fetch();

        if (!$cliente) {
            $error = "Cliente non trovato.";
            $action = ''; // Torna alla lista
        }
    } catch (Exception $e) {
        $error = 'Errore nel caricamento del cliente: ' . $e->getMessage();
        $action = ''; // Torna alla lista
    }
}

// Carica lista clienti dalla view (oppure query diretta se la view non esiste)
try {
    $db = getDB();

    // Query diretta dalle view per i dati dei clienti con tessera dato un negozio se il negozio non viene fornito
    $stmt = $db->query("SELECT c.id_cliente as id, c.nome, c.cognome, u.email,
                               COALESCE(t.id_tessera::text, 'N/A') as numero_tessera,
                               COALESCE(t.saldo_punti, 0) as saldo_punti
                        FROM negozi.clienti c
                        JOIN auth.utenti u ON c.utente = u.id_utente
                        LEFT JOIN negozi.cliente_tessera ct ON c.id_cliente = ct.cliente
                        LEFT JOIN negozi.tessere t ON ct.tessera = t.id_tessera
                        ORDER BY c.id_cliente");
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
            <h2>Gestione Clienti</h2>
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

        <?php if ($action === 'new' || $action === 'modifica'): ?>
            <!-- Form Nuovo Cliente -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="bi <?= $action === 'new' ? 'bi-person-plus' : 'bi-person-gear' ?>"></i> <?= $action === 'new' ? 'Nuovo Cliente' : 'Modifica Cliente' ?></h4>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                     <input type="hidden" name="action" value="<?= $action== "new" ? "create" : "update" ?>">
                                <!-- Nome e Cognome -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="nome" class="form-label">Nome*</label>
                                            <input type="text" class="form-control" id="nome" name="nome"
                                                   value="<?= htmlspecialchars($_POST['nome'] ?? $cliente['nome'] ?? '') ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="cognome" class="form-label">Cognome</label>
                                            <input type="text" class="form-control" id="cognome" name="cognome"
                                                   value="<?= htmlspecialchars($_POST['cognome'] ?? $cliente['cognome'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>
                                <!-- CF -->
                                <div class="mb-3">
                                    <label for="cf" class="form-label">CF*</label>
                                    <input type="text" class="form-control" id="cf" name="cf"
                                           value="<?= htmlspecialchars($_POST['cf'] ?? $cliente['cf'] ?? '') ?>" required>
                                </div>
                                <!-- Email -->
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email*</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                           value="<?= htmlspecialchars($_POST['email'] ?? $cliente['email'] ?? '') ?>" required>
                                </div>
                                <!-- Telefono -->
                                <div class="mb-3">
                                    <label for="telefono" class="form-label">Telefono</label>
                                    <input type="tel" class="form-control" id="telefono" name="telefono"
                                           value="<?= htmlspecialchars($_POST['telefono'] ?? $cliente['telefono'] ?? '') ?>">
                                </div>
                                <!-- Nota Informativa -->
                                <?php if ($action === 'new'): ?>
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle"></i>
                                        <strong>Nota:</strong> La password di default sarà "password123". La tessera fedeltà può essere richiesta successivamente.
                                    </div>
                                <?php endif; ?>
                                <!-- Bottoni per Invio Dati-->
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-success">            
                                        <i class="bi bi-check-circle"></i> 
                                        <?= $action === 'new' ? 'Crea Cliente' : 'Aggiorna Cliente' ?>
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
                    <h5>Lista Clienti</h5>
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
                                        <th>Email</th>
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
                                                <span><?= $cliente['id'] ?></span>
                                            </td>
                                            <td>
                                                <strong><?= $cliente['email'] ?></strong>
                                            </td>
                                            <td>
                                                <?= $cliente['nome'] . ' ' . $cliente['cognome'] ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?= $cliente['numero_tessera'] ?></span>
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
        
            window.location.href = 'gestione_clienti.php?action=modifica&id=' + id;
        }
        
        function eliminaCliente(id, nome) {
            if (confirm('Sei sicuro di voler eliminare il cliente "' + nome + '"?')) {
                // Crea form nascosto per eliminazione
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
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