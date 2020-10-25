<?php

/**
 * Description of DB
 *
 * @author edily
 */
class DbPdoMySql
{

    protected $pdo = null;
    protected $type;
    protected $db = null;
    protected $host;
    protected $user;
    protected $password;
    protected $database;
    protected $port;
    protected $socket;
    protected $lastInsertId;
    protected $numRows = 0;
    protected $affectedRows = 0;
    protected $result;
    protected $msgError;
    protected $success;
    protected $numCols;
    protected $errorNum;
    protected $lastQuerySql;
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function connect()
    {
        if ($this->pdo !== null) {
            return false;
        }
        try
        {
            $this->getConfig();
            $this->checkConfig();
            $config = "{$this->type}:host=$this->host;dbname=$this->database;port=$this->port";
            $config .= ($this->type == 'mysql') ? ";charset=utf8" : "";
            $this->pdo = new \PDO($config, $this->user, $this->password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            //$this->pdo->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET NAMES iso-8859-1");
            //$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $this->pdo;
        } catch (\PDOException $e)
        {
            echo "Não foi possível conectar ao banco - Erro: " . $e->getMessage() . " Trace: " . $e->getTraceAsString();
        }
    }

    public function query($query)
    {
        $this->lastQuerySql = $query;
        try
        {
            $this->connect();
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $error = $stmt->errorInfo();
            if ($error[0] != 0) {
                $msg = $stmt->errorInfo() . "<br/>" . $query;
                $this->msgError = $msg;
                $this->errorNum = $stmt->errorCode();
                throw new \Exception($msg);
            }
            $this->lastInsertId = $this->pdo->lastInsertId();
            $this->affectedRows = (int) $stmt->rowCount();
            $this->numRows = $this->affectedRows;
        } catch (\PDOException $e)
        {
            $this->lastInsertId = 0;
            $this->affectedRows = 0;
            $this->msgError = $e->getMessage() . " Trace: " . $e->getTraceAsString() . " Query:" . $query;
            echo $this->msgError;
            return false;
        }
        $this->disconect();
        return $stmt;
    }

    public function select($query)
    {
        $stmt = $this->query($query);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->numRows = count($rows);
        return $rows;
    }

    private function disconect()
    {
        $this->pdo = null;
    }

    private function getConfig()
    {
        $config = $this->config;
        $this->type = trim($config['type']);
        $this->host = trim($config['host']);
        $this->user = trim($config['user']);
        $this->password = trim($config['password']);
        $this->database = trim($config['name']);
        $this->port = trim($config['port']);
        return true;
    }

    private function checkConfig()
    {
        if (empty($this->type)) {
            throw new Exception("Configuração type ausente");
        }

        if (empty($this->host)) {
            throw new Exception("Configuração host ausente");
        }

        if (empty($this->user)) {
            throw new Exception("Configuração user ausente");
        }

        if (empty($this->password)) {
            throw new Exception("Configuração password ausente");
        }

        if (empty($this->database)) {
            throw new Exception("Configuração database ausente");
        }

        if (empty($this->port)) {
            throw new Exception("Configuração port ausente");
        }
    }

    public function getSubstringSQL($order)
    {
        $this->getConfig();
        switch ($this->type) {
            case "mysql":
                $order = ($order === 1) ? "1)" : "-1)";
                return "SUBSTRING_INDEX(coordenadas, ','," . $order;
            case "pgsql":
                $order = ($order === 1) ? "1)" : "2)";
                return "SPLIT_PART(coordenadas, ','," . $order;
            default:
                return "";
        }
    }

    public function getLastQuerySql()
    {
        return $this->lastQuerySql;
    }

}
