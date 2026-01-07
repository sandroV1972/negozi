<?php
session_start();

// Verifica se l'utente è loggato ed è un manager
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_tipo'] !== 'manager') {
    header('Location: ../index.php?error=access_denied');
    exit;
}

require_once '../config/database.php';

$error = '';
$message = '';

// Genera token CSRF se non esiste
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Gestione cambio password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                $message = "Password cambiata con successo!";
            } else {
                $error = "La password attuale non e' corretta.";
            }
        } catch (Exception $e) {
            $error = "Errore: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambia Password - Dashboard Manager</title>
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
                        <li><a class="dropdown-item active" href="cambia_password.php">
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
                <li class="breadcrumb-item active">Cambia Password</li>
            </ol>
        </nav>

        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-key"></i> Cambia Password</h5>
                    </div>
                    <div class="card-body">
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

                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                            <div class="mb-3">
                                <label for="password_attuale" class="form-label">
                                    <i class="bi bi-lock"></i> Password Attuale
                                </label>
                                <input type="password" class="form-control" id="password_attuale"
                                       name="password_attuale" required>
                            </div>

                            <div class="mb-3">
                                <label for="nuova_password" class="form-label">
                                    <i class="bi bi-lock-fill"></i> Nuova Password
                                </label>
                                <input type="password" class="form-control" id="nuova_password"
                                       name="nuova_password" required minlength="8">
                                <div class="form-text">Minimo 8 caratteri</div>
                            </div>

                            <div class="mb-3">
                                <label for="conferma_password" class="form-label">
                                    <i class="bi bi-lock-fill"></i> Conferma Nuova Password
                                </label>
                                <input type="password" class="form-control" id="conferma_password"
                                       name="conferma_password" required minlength="8">
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg"></i> Cambia Password
                                </button>
                                <a href="dashboard_manager.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Torna alla Dashboard
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
