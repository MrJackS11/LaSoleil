<?php
$servidor = "localhost";
$usuario = "postgres";
$psw = "123456"; 
$baseDatos = "postgres";
$puerto = 5432;

if (!class_exists('PgResult')) {
    class PgResult {
        private $rows = [];
        private $currentIndex = 0;
        public $num_rows = 0;

        public function __construct($pdoStmt) {
            if ($pdoStmt) {
                $this->rows = $pdoStmt->fetchAll(PDO::FETCH_ASSOC);
                $this->num_rows = count($this->rows);
            }
        }

        public function fetch_assoc() {
            if ($this->currentIndex < $this->num_rows) {
                return $this->rows[$this->currentIndex++];
            }
            return null;
        }

        public function data_seek($offset = 0) {
            $this->currentIndex = (int)$offset;
        }
    }
}

if (!class_exists('PgStatement')) {
    class PgStatement {
        private $pdoStmt;
        private $params = [];
        public $error = "";

        public function __construct($pdo, $sql) {
            $this->pdoStmt = $pdo->prepare($sql);
        }

        public function bind_param($types, &...$args) {
            $this->params = [];
            foreach ($args as $k => &$v) {
                $this->params[$k] = &$v;
            }
        }

        public function execute($params = null) {
            $p = $params ?? array_values($this->params);
            $res = $this->pdoStmt->execute($p);
            if (!$res) {
                $err = $this->pdoStmt->errorInfo();
                $this->error = $err[2] ?? "Error en la ejecución";
            }
            return $res;
        }
    }
}

if (!class_exists('PgConnection')) {
    class PgConnection {
        private $pdo;
        public $error = "";
        public $connect_error = null;

        public function __construct($host, $user, $pass, $db, $port = 5432) {
            try {
                $dsn = "pgsql:host=$host;port=$port;dbname=$db";
                $this->pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
            } catch (PDOException $e) {
                $this->connect_error = $e->getMessage();
            }
        }

        private function translateSql($sql) {
            // Compatibilidad de funciones MySQL -> PostgreSQL
            $sql = preg_replace("/DATE_SUB\(NOW\(\),\s*INTERVAL\s*(\d+)\s*DAY\)/i", "(NOW() - INTERVAL '$1 day')", $sql);
            $sql = str_ireplace("DATE_FORMAT(fecha, '%Y-%m')", "TO_CHAR(fecha, 'YYYY-MM')", $sql);
            
            // Compatibilidad de GROUP BY estricto en PostgreSQL (estadisticas.php)
            if (stripos($sql, 'GROUP BY v.producto_id') !== false && stripos($sql, 'p.nombre') !== false && stripos($sql, 'GROUP BY v.producto_id, p.nombre') === false) {
                $sql = str_ireplace('GROUP BY v.producto_id', 'GROUP BY v.producto_id, p.nombre', $sql);
            }
            return $sql;
        }

        public function query($sql) {
            if (!$this->pdo) {
                $this->error = "Sin conexión a la base de datos.";
                return false;
            }

            $sql = $this->translateSql($sql);
            $trimmed = trim(strtoupper($sql));
            $isSelect = str_starts_with($trimmed, 'SELECT') || str_starts_with($trimmed, 'SHOW');

            if ($isSelect) {
                $stmt = $this->pdo->query($sql);
                if ($stmt === false) {
                    $err = $this->pdo->errorInfo();
                    $this->error = $err[2] ?? "Error en la consulta";
                    return false;
                }
                return new PgResult($stmt);
            } else {
                $res = $this->pdo->exec($sql);
                if ($res === false) {
                    $err = $this->pdo->errorInfo();
                    $this->error = $err[2] ?? "Error al ejecutar consulta";
                    return false;
                }
                return true;
            }
        }

        public function prepare($sql) {
            if (!$this->pdo) {
                $this->error = "Sin conexión a la base de datos.";
                return false;
            }
            $sql = $this->translateSql($sql);
            return new PgStatement($this->pdo, $sql);
        }

        public function set_charset($charset) {
            return true;
        }
    }
}

$conn = new PgConnection($servidor, $usuario, $psw, $baseDatos, $puerto);
if ($conn->connect_error) {
    die("Error de conexión a la base de datos: " . $conn->connect_error);
}
?>