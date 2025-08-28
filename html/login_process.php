<?php
// Debug avanzato
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

echo "<h3>DEBUG LOGIN PROCESS</h3>";
echo "Metodo: " . $_SERVER['REQUEST_METHOD'] . "<br>";

// Verifica che il file database.php esista
if (!file_exists('config/database.php')) {
    die("ERRORE: File config/database.php non trovato!");
}
echo "✅ File database.php trovato<br>";

session_start();
echo "✅ Sessione avviata<br>";

try {
    require_once 'config/database.php';
    echo "✅ Database.php caricato<br>";
} catch (Exception $e) {
    die("ERRORE nel caricamento database.php: " . $e->getMessage());
}

// Funzione per verificare la password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Verifica variabili POST
echo "<h4>Dati ricevuti:</h4>";
echo "POST data: <pre>" . print_r($_POST, true) . "</pre>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    echo "Email pulita: '" . htmlspecialchars($email) . "'<br>";
    echo "Password ricevuta: " . (empty($password) ? "VUOTA" : strlen($password) . " caratteri") . "<br>";
    
    if (empty($email) || empty($password)) {
        header('Location: index.php?error=empty_fields');
        exit;
    }
    
    try {
        echo "Tentativo connessione database...<br>";
        $db = Database::getInstance();
        echo "✅ Database connesso<br>";
        
        echo "Tentativo query utente...<br>";
        // CORRETTO: usa i nomi colonne corretti del tuo database
        $sql = "SELECT id_utente as id, email, username, password_hash as password, attivo FROM auth.utenti WHERE email = ? AND attivo = TRUE";
        echo "SQL: " . $sql . "<br>";
        echo "Parametri: [" . $email . "]<br>";
        
        $stmt = $db->query($sql, [$email]);
        echo "✅ Query eseguita<br>";
        
        $user = $stmt->fetch();
        echo "Risultato query: " . ($user ? "UTENTE TROVATO" : "NESSUN UTENTE") . "<br>";
        
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
                $_SESSION['user_nome'] = $user['username']; // Usa username come nome
                $_SESSION['user_cognome'] = ''; // Non hai cognome nel DB
                $_SESSION['user_tipo'] = 'manager'; // Assumiamo manager per ora
                $_SESSION['logged_in'] = true;
                
                echo "Sessione impostata: <pre>" . print_r($_SESSION, true) . "</pre>";
                
                echo "👔 Redirect a dashboard manager...<br>";
                echo '<a href="pages/dashboard_manager.php">Clicca qui per andare alla dashboard</a><br>';
                echo '<meta http-equiv="refresh" content="3;url=pages/dashboard_manager.php">';
                
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

echo "<hr><h4>Debug completato</h4>";
?>