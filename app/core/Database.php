<?php
class Database {
    private $host = 'localhost';
    private $user = 'root';
    private $pass = '';
    private $dbname = 'lensarental';
    private $dbh;
    private $stmt;

    public function __construct() {
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname;
        $options = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
        ];

        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        } catch(PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception('Database connection failed');
        }
    }

    public function query($query) {
        try {
            $this->stmt = $this->dbh->prepare($query);
        } catch(PDOException $e) {
            error_log("Query preparation error: " . $e->getMessage());
            throw new Exception('Query preparation failed');
        }
    }

    public function bind($param, $value, $type = null) {
        try {
            if(is_null($type)) {
                switch(true) {
                    case is_int($value):
                        $type = PDO::PARAM_INT;
                        break;
                    case is_bool($value):
                        $type = PDO::PARAM_BOOL;
                        break;
                    case is_null($value):
                        $type = PDO::PARAM_NULL;
                        break;
                    default:
                        $type = PDO::PARAM_STR;
                }
            }
            $this->stmt->bindValue($param, $value, $type);
        } catch(PDOException $e) {
            error_log("Parameter binding error: " . $e->getMessage());
            throw new Exception('Parameter binding failed');
        }
    }

    public function execute() {
        try {
            return $this->stmt->execute();
        } catch(PDOException $e) {
            error_log("Query execution error: " . $e->getMessage());
            throw new Exception('Query execution failed');
        }
    }

    public function resultSet() {
        try {
            $this->execute();
            return $this->stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Result set error: " . $e->getMessage());
            throw new Exception('Failed to fetch result set');
        }
    }

    public function single() {
        try {
            $this->execute();
            return $this->stmt->fetch();
        } catch(PDOException $e) {
            error_log("Single result error: " . $e->getMessage());
            throw new Exception('Failed to fetch single result');
        }
    }

    public function rowCount() {
        try {
            return $this->stmt->rowCount();
        } catch(PDOException $e) {
            error_log("Row count error: " . $e->getMessage());
            throw new Exception('Failed to get row count');
        }
    }

    public function beginTransaction() {
        try {
            return $this->dbh->beginTransaction();
        } catch(PDOException $e) {
            error_log("Begin transaction error: " . $e->getMessage());
            throw new Exception('Failed to begin transaction');
        }
    }

    public function commit() {
        try {
            return $this->dbh->commit();
        } catch(PDOException $e) {
            error_log("Commit error: " . $e->getMessage());
            throw new Exception('Failed to commit transaction');
        }
    }

    public function rollBack() {
        try {
            return $this->dbh->rollBack();
        } catch(PDOException $e) {
            error_log("Rollback error: " . $e->getMessage());
            throw new Exception('Failed to rollback transaction');
        }
    }

    public function lastInsertId() {
        try {
            return $this->dbh->lastInsertId();
        } catch(PDOException $e) {
            error_log("Last insert ID error: " . $e->getMessage());
            throw new Exception('Failed to get last insert ID');
        }
    }
} 