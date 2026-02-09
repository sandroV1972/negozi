<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Retro Gaming Store</title>
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Retro Arcade Theme -->
    <link href="css/retro-arcade.css" rel="stylesheet">
</head>
<body>
    <!-- Messaggi di errore/successo -->
    <?php if (isset($_GET['error'])): ?>
        <div class="position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 1050;">
            <div class="alert alert-danger alert-dismissible fade show">
                <?php
                switch ($_GET['error']) {
                    case 'empty_fields':
                        echo '<i class="bi bi-exclamation-triangle"></i> Inserisci email e password.';
                        break;
                    case 'invalid_password':
                        echo '<i class="bi bi-x-circle"></i> Password errata.';
                        break;
                    case 'user_not_found':
                        echo '<i class="bi bi-person-x"></i> Utente non trovato.';
                        break;
                    case 'access_denied':
                        echo '<i class="bi bi-shield-x"></i> Accesso negato.';
                        break;
                    default:
                        echo '<i class="bi bi-bug"></i> Errore di sistema.';
                }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['message']) && $_GET['message'] === 'logged_out'): ?>
        <div class="position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 1050;">
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> Logout effettuato con successo.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <?php session_start(); ?>
    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
        <?php
            // Utente già loggato, reindirizza alla dashboard appropriata
            $redirect_url = ($_SESSION['user_tipo'] === 'manager') ? 'pages/dashboard_manager.php' : 'pages/dashboard_cliente.php';
            header("Location: $redirect_url");
            exit;
        ?>
    <?php endif; ?>

    <div class="login-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="card login-card">
                        <div class="card-body p-5">
                            <!-- Logo e Titolo -->
                            <div class="text-center mb-4">
                                <img src="images/logo.jpg" alt="Logo" class="mb-3 login-logo">
                                <h1 class="login-title">Retro Gaming Store</h1>
                            </div>

                            <!-- Form di Login -->
                            <form action="login_process.php" method="POST" id="loginForm">
                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        <i class="bi bi-envelope"></i> Email
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           placeholder="inserisci@email.com" required autofocus>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">
                                        <i class="bi bi-lock"></i> Password
                                    </label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" 
                                               placeholder="Inserisci password" required>
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="d-grid mb-4">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-box-arrow-in-right"></i> Accedi
                                    </button>
                                </div>
                            </form>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="text-center mt-4 text-white">
                        <small>
                            <i class="bi bi-mortarboard"></i> 
                            Progetto Basi di Dati - Università
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });

        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Inserisci email e password per continuare.');
                return false;
            }

            // Disabilita il pulsante durante l'invio
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Accesso in corso...';
        });

        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>
