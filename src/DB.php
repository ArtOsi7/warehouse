<?php


class DB
{
    private $conn;

    /**
     * DB constructor.
     * @param array $dbParams
     */
    public function __construct(array $dbParams)
    {
        $this->connect($dbParams);
    }

    /**
     * @param array $dbParams
     */
    private function connect(array $dbParams)
    {
        $dsn = "mysql:host={$dbParams['db_host']};dbname={$dbParams['db_name']};charset=utf8";
        try {
            $this->conn = new PDO($dsn, $dbParams['db_username'], $dbParams['db_password']);
            // Set the PDO error mode to exception
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        //    echo "Connection successful!";
        } catch (PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }
    }

    /**
     * @param $sql
     * @param array $params
     * @return mixed
     * @TODO: change die() with better exceptions handling
     */
    public function executeQuery($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            die("Query failed: " . $e->getMessage() . $e->getTraceAsString());
        }
    }

    /**
     * @param $sql
     * @param array $params
     * @return mixed
     * @TODO: change die() with better exceptions handling
     */
    public function fetchAll($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all results as associative array
        } catch (PDOException $e) {
            die("Query failed: " . $e->getMessage() .$e->getTraceAsString());
        }
    }

    /**
     * @param $sql
     * @param array $params
     * @return mixed
     * @TODO: change die() with better exceptions handling
     */
    public function fetchOne($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC); // Fetch a single row
        } catch (PDOException $e) {
            die("Query failed: " . $e->getMessage());
        }
    }


    private function close()
    {
        $this->conn = null;
    }
}