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
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue; // Salta linee vuote e commenti
        }

        if (strpos($line, '=') === false) {
            continue; // Salta linee senza =
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

class PGResult {
    private $result;

    public function __construct($result) {
        if (!$result) {
            throw new InvalidArgumentException('Invalid PG result resource.');
        }
        $this->result = $result;
    }

    public function fetch($mode = PGSQL_ASSOC) {
        return pg_fetch_array($this->result, null, $mode);
    }

    public function fetchAll($mode = PGSQL_ASSOC) {
        $rows = [];
        while ($row = pg_fetch_array($this->result, null, $mode)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function rowCount() {
        return pg_num_rows($this->result);
    }
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
        // pg_connect (NON persistent) - la connessione si chiude a fine richiesta
        $conn_string = sprintf(
            "host=%s port=%s dbname=%s user=%s password=%s",
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_USER,
            DB_PASS
        );

        $this->connection = @pg_connect($conn_string);

        if (!$this->connection) {
            $error = pg_last_error() ?: "Connessione fallita";
            error_log("Database connection error: " . $error);
            die("Errore connessione database. Controlla la configurazione.");
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function testConnection() {
        try {
            $result = pg_query($this->connection, "SELECT version(), current_database(), current_user");
            if (!$result) {
                throw new Exception(pg_last_error($this->connection));
            }

            $row = pg_fetch_assoc($result);  // Solo 1 parametro!
            pg_free_result($result);

            return [
                'success' => true,
                'version' => $row['version'],
                'database' => $row['current_database'],
                'user' => $row['current_user'],
                'message' => 'Connessione PostgreSQL riuscita!'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Errore: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Valida l'SQL passato per prevenire l'esecuzione di strutture pericolose.
     *
     * - Richiede una stringa non vuota
     * - Consente una sola istruzione (al massimo un ';' finale)
     * - Applica una whitelist sul comando iniziale (SELECT/INSERT/UPDATE/DELETE/CREATE/ALTER/DROP)
     * @param mixed $sql
     * @throws InvalidArgumentException se la query non è valida
     */
    private function validateSql($sql) {
        if (!is_string($sql)) {
            throw new InvalidArgumentException('SQL query must be a string.');
        }

        $trimmed = trim($sql);
        if ($trimmed === '') {
            throw new InvalidArgumentException('SQL query cannot be empty.');
        }

        // Impedisci più istruzioni nella stessa query
        $semicolonPos = strpos($trimmed, ';');
        if ($semicolonPos !== false && $semicolonPos < strlen($trimmed) - 1) {
            throw new InvalidArgumentException('Multiple SQL statements are not allowed.');
        }

        // Whitelist del comando iniziale
        $firstToken = strtok($trimmed, " \t\n\r");
        $firstTokenUpper = strtoupper($firstToken);
        $allowedCommands = [
            'SELECT',
            'INSERT',
            'UPDATE',
            'DELETE',
            'CREATE',
            'ALTER',
            'DROP',
            'BEGIN',
            'COMMIT',
            'ROLLBACK'
        ];

        if (!in_array($firstTokenUpper, $allowedCommands, true)) {
            throw new InvalidArgumentException('Disallowed SQL command: ' . $firstTokenUpper);
        }
    }
    
    public function query($sql, $params = []) {
        try {
            // Valida la query prima di prepararla
            $this->validateSql($sql);

            if (empty($params)) {
                $stmt = pg_query($this->connection, $sql);
            } else {
                // Converti i placeholder da ? a $1, $2, $3...
                $count = 1;
                $pg_sql = preg_replace_callback('/\?/', function() use (&$count) {
                    return '$' . $count++;
                }, $sql);

                $stmt = pg_query_params($this->connection, $pg_sql, $params);
            }

            if (!$stmt) {
                throw new Exception(pg_last_error($this->connection));
            }

            return new PGResult($stmt);
        } catch (Exception $e) {
            error_log("Query error: " . $e->getMessage() . " SQL: " . $sql);
            throw $e;
        }
    }

}

/**
 * Restituisce l'istanza singleton della classe Database.
 *
 * Utilizzare questa funzione helper per ottenere una connessione
 * al database da qualsiasi parte dell'applicazione senza creare
 * nuove istanze di Database.
 *
 * @return Database Istanza singleton di Database.
 */
function getDB() {
    return Database::getInstance();
}
?>