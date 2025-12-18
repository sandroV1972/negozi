<?php

// Verifica che il file database.php esista
if (!file_exists('config/database.php')) {
    die("ERRORE: File config/database.php non trovato!");
}

session_start();

try {
    require_once 'config/database.php';
} catch (Exception $e) {
    die("ERRORE nel caricamento database.php: " . $e->getMessage());
}

// Funzione per verificare la password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Verifica variabili POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        header('Location: index.php?error=empty_fields');
        exit;
    }
    
    try {
        $db = Database::getInstance();
   
        echo "Tentativo query utente...<br>";
        // Query con JOIN per ottenere anche il ruolo
        $sql = "SELECT u.id_utente as id, u.email, u.username, u.password, u.attivo, r.nome as ruolo
                FROM auth.utenti u
                LEFT JOIN auth.utente_ruolo ur ON u.id_utente = ur.id_utente
                LEFT JOIN auth.ruolo r ON ur.id_ruolo = r.id_ruolo
                WHERE u.email = ? AND u.attivo = TRUE";
        echo "SQL: " . $sql . "<br>";
        echo "Parametri: [" . $email . "]<br>";

        $stmt = $db->query($sql, [$email]);
 
        $user = $stmt->fetch();
    
        if ($user) {
            echo "<h4>Dati utente (mappati):</h4>";
            echo "<pre>" . print_r($user, true) . "</pre>";
            
            // Test verifica password
            echo "Test password...<br>";
            echo "Password hash nel DB: " . substr($user['password'], 0, 20) . "...<br>";
            echo "Password inserita: " . $password . "<br>";
            
            $password_check = password_verify($password, $user['password']);
            echo "Verifica password: " . ($password_check ? "✅ OK" : "❌ FALLITA") . "<br>";
            
            if ($password_check) {
                echo "<h4>LOGIN RIUSCITO!</h4>";
                echo "Imposto sessione...<br>";

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_nome'] = $user['username'];
                $_SESSION['user_cognome'] = '';
                $_SESSION['user_tipo'] = $user['ruolo'] ?? 'cliente'; // Usa ruolo dal DB
                $_SESSION['logged_in'] = true;

                echo "Sessione impostata: <pre>" . print_r($_SESSION, true) . "</pre>";

                // Redirect in base al ruolo
                $redirect_url = ($user['ruolo'] === 'manager') ? 'pages/dashboard_manager.php' : 'pages/dashboard_cliente.php';


                // Aggiorna ultimo accesso
                $db->query("UPDATE auth.utenti SET ultimo_accesso = NOW() WHERE id_utente = ?", [$user['id']]);

                exit;
            } else {
                header('Location: index.php?error=invalid_password');
                exit;
            }
        } else {
            header('Location: index.php?error=user_not_found');
            exit;
        }
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        header('Location: index.php?error=system_error');
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}

?>