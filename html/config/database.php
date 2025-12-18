<?php
/**
 * Carica le variabili di ambiente da un file .env.
 *
 * @param string $path Percorso del file .env da caricare.
 * @return void
 *
 * Termina l'esecuzione con die() se il file .env non viene trovato.
 */
function loadEnv($path) {
    if (!file_exists($path)) {
        die("File .env non trovato in: " . $path);
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        die("Errore lettura file .env");
    }
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Salta i commenti
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Carica .env dalla root del progetto
loadEnv(__DIR__ . '/../../.env');

// Configurazione database da variabili ambiente
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '5432');
define('DB_NAME', getenv('DB_NAME') ?: '');
define('DB_USER', getenv('DB_USER') ?: '');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Verifica che le variabili essenziali siano definite
if (empty(DB_NAME) || empty(DB_USER)) {
    die("Errore: Configurazione database incompleta. Verifica il file .env");
}

class Database {
    private $connection;
    private static $instance = null;
    
    private function __construct() {
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            die("Errore connessione database. Controlla la configurazione.");
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function testConnection() {
        try {
            $stmt = $this->connection->query("SELECT version(), current_database(), current_user");
            $result = $stmt->fetch();
            return [
                'success' => true,
                'version' => $result['version'],
                'database' => $result['current_database'],
                'user' => $result['current_user'],
                'message' => 'Connessione PostgreSQL riuscita!'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Errore: ' . $e->getMessage()
            ];
        }
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query error: " . $e->getMessage() . " SQL: " . $sql);
            throw $e;
        }
    }
}

function getDB() {
    return Database::getInstance()->getConnection();
}
?>