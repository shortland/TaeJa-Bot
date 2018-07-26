<?php

class Db {

    private $username;

    private $password;

    private $host;

    private $database;

    private $connection;

    public function __construct(array $configData) {
        $dbConfig = $configData['database'];
        $this->username = $dbConfig['username'];
        $this->password = $dbConfig['password'];
        $this->host = $dbConfig['host'];
        $this->database = $dbConfig['database'];
    }

    /**
     * @throws Exception
     */
    public function connect() {
        $connection = new mysqli(
            $this->host,
            $this->username,
            $this->password,
            $this->database
        );

        if (!$connection->set_charset("utf8mb4")) {
            throw new Exception(sprintf("Error loading character set utf8mb4: %s\n", $connection->error));
            exit();
        }

        if ($connection->connect_errno) {
            throw new Exception(sprintf("Db Error: Database connection failed: %s\n", $connection->connect_error));
            exit();
        }

        $this->connection = $connection;
    }

    /**
     * @throws Exception
     */
    public function doRawQuery(string $query, array $params = [], bool $verbose = false) {
        $query = $this->addParams($query, $params);

        if ($verbose) {
            echo $query;
        }

        if ($result = $this->connection->query($query)) {
            return $result;
        }
        else {
            throw new Exception(sprintf("Db Error: %s\n", mysqli_error($this->connection)));
        }
    }

    public function disconnect() {
        $this->connection->close();
    }

    /**
     * @throws Exception
     */
    private function addParams($query, $params) {
        if ($params) {
            foreach ($params as $param) {
                $queryNew = preg_replace('/(\?)/', sprintf("'%s'", $param), $query, 1);
                if ($queryNew == $query) {
                    throw new Exception("Db Error: Number of given parameters and available replacements in query string do not match\n");
                }
                $query = $queryNew;
            }
        }

        return $query;
    }
}