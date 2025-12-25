<?php
session_start();

// Distruggi tutte le variabili di sessione
$_SESSION = [];

// Distruggi il cookie di sessione se esiste
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Distruggi la sessione
session_destroy();

// Redirect alla pagina di login
header('Location: index.php?message=logout_success');
exit;
?>
