<?php

/**
 * Description of Query
 *
 * @author edily
 */
class SqlProcessor
{

    const BASE_DIR = __DIR__ . "/../../";
    const MERGED_QUEUE_EXECUTED_FILENAME = "merged.sql";

    private $configs = [];
    private $currentConfig;
    private $currentQueueFolder;
    private $currentQueueExecutedFolder;
    private $currentQueueFolderName;
    private $currentMainFile;
    private $currentPartFile;
    private $currentPartFileName;
    private $currentQuery;
    private $currentDatabases;
    private $currentDatabase;
    private $author;
    private $description;
    private $infoTable = "database_info_slq";

    public function __construct(array $configs)
    {
        $this->configs = $configs;
    }

    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    public function setAuthor($author)
    {
        $this->author = $author;
        return $this;
    }

    public function run()
    {
        echo "Iniciando..." . PHP_EOL;

        $this->moveSqlFiles();

        foreach ($this->configs as $this->currentConfig) {
            $this->runItem();
        }

        $this->mergeExecuted();

        $this->validateAllLogTables();

        echo PHP_EOL . "Finalizado!" . PHP_EOL;
    }

    private function getMainFolderFromCurrentConfig()
    {
        return realpath($this->currentConfig['mainfolder']);
    }

    private function moveSqlFiles()
    {
        foreach ($this->configs as $this->currentConfig) {

            $originDir = $this->getMainFolderFromCurrentConfig();
            $destinationDir = realpath($this->currentConfig['queuefoldername'] . "/queue");
            $destinationExecutedDir = realpath($this->currentConfig['queuefoldername'] . "/queue/executed");

            foreach (scandir($originDir) as $file) {

                if (array_search($file, [".", ".."]) !== false) {
                    continue;
                }

                $ofilename = $originDir . DIRECTORY_SEPARATOR . $file;
                $dfilename = $destinationDir . DIRECTORY_SEPARATOR . $file;
                $dfilenameExecuted = $destinationExecutedDir . DIRECTORY_SEPARATOR . $file;

                if (file_exists($dfilename) === true) {
                    //echo "ARQUIVO EXISTE NO DESTINO: " . $dfilename;
                    continue;
                }

                if (file_exists($dfilenameExecuted) === true) {
                    //echo "ARQUIVO EXISTE NO DESTINO: " . $dfilenameExecuted;
                    continue;
                }

                echo "COPIANDO: " . $ofilename;
                echo " > " . $dfilename . PHP_EOL;

                copy($ofilename, $dfilename);
            }
        }
    }

    private function runItem()
    {
        $this->currentDatabases = $this->currentConfig['databases'];
        $this->currentQueueFolderName = $this->currentConfig['queuefoldername'];
        $this->currentMainFile = $this->currentConfig['mainfile'];
        //$this->currentQueueFolder = self::BASE_DIR . DIRECTORY_SEPARATOR . $this->currentQueueFolderName . "/queue/";
        $this->currentQueueFolder = $this->currentQueueFolderName . "/queue/";
        //$this->currentQueueExecutedFolder = self::BASE_DIR . DIRECTORY_SEPARATOR . $this->currentQueueFolderName . "/queue/executed/";
        $this->currentQueueExecutedFolder = $this->currentQueueFolderName . "/queue/executed/";

        $this->createInfoTablesIfNotExists();

        $this->runParts();
    }

    private function runParts()
    {
        foreach ($this->getFilesFromDirSortAlphabetically($this->currentQueueFolder) as $this->currentPartFileName) {

            if ($this->currentPartFileNameIsValid() === false) {
                continue;
            }

            $this->runPart();
        }
    }

    private function currentPartFileNameIsValid()
    {
        $ext = substr($this->currentPartFileName, -3);
        return $ext === "sql" ? true : false;
    }

    private function runPart()
    {
        $this->currentPartFile = $this->currentQueueFolder . DIRECTORY_SEPARATOR . $this->currentPartFileName;

        if (!file_exists($this->currentPartFile)) {
            throw new Exception("Arquivo não encontrado");
        }

//echo $this->currentPartFile . PHP_EOL;
        $this->currentQuery = file_get_contents($this->currentPartFile);
        $this->runQueryByDatabase();
        $this->moveFileToExecutedFolder();
    }

    private function extractDateFromFileName($filename)
    {
        $y = substr($filename, 0, 4);
        $m = substr($filename, 5, 2);
        $d = substr($filename, 8, 2);
        $h = substr($filename, 11, 2);
        $i = substr($filename, 14, 2);
        $s = substr($filename, 17, 2);

        $datetime = $y . "-" . $m . "-" . $d . " " . $h . ":" . $i . ":" . $s;

        if (checkdate($m, $d, $y) === false) {
            throw new \Exception("Data inválida: " . $datetime);
        }

        return $datetime;
    }

    private function extractDateFromCurrentFileName()
    {
        return $this->extractDateFromFileName($this->currentPartFileName);
    }

    private function extractDateFromPreviousFileName()
    {
        $previousFilename = $this->getPreviousFilename($this->currentPartFileName);

        if ($previousFilename === false) {
            return "";
        }

        return $this->extractDateFromFileName($previousFilename);
    }

    public function validateAllLogTables()
    {
        echo EOL . " VALIDATING DATABASES " . EOL;

        foreach ($this->configs as $this->currentConfig) {
            foreach ($this->currentConfig['databases'] as $this->currentDatabase) {
                $this->validateLogTable();
            }
        }
    }

    private function validateLogTable()
    {
        $query = "SELECT * FROM `{$this->infoTable}` ORDER BY sql_date DESC";

        $db = new DbPdoMySql($this->currentDatabase);
        $rows = $db->select($query);

        echo " - " . $this->currentDatabase["name"];
        $dbStatusOk = true;
        $dbErrorMsg = "";

        foreach ($rows as $key => $row) {
            if ($key === 0) {
                continue;
            }

            $pKey = $key - 1;

            $pRow = $rows[$pKey];

            $statusOk = ($pRow["sql_previous_date"] === $row["sql_date"]);
            $statusTx = $statusOk ? "OK" : "ERRO";

            if ($statusOk === false) {
                $dbStatusOk = false;
                $dbErrorMsg .= "   ~"
                        . " ID:" . $row["id"]
                        . " Data:" . $row["sql_date"]
                        . " Anterior:" . $row["sql_previous_date"]
                        . EOL;
            }
        }

        echo $dbStatusOk === true ? " [OK]" : " [ERROR] " . EOL;
        echo $dbErrorMsg . EOL;
    }

    private function createInfoTablesIfNotExists()
    {
        $query = "CREATE TABLE IF NOT EXISTS `{$this->infoTable}` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `sql_author` VARCHAR(60) DEFAULT NULL,
            `sql_date` TIMESTAMP NULL,
            `sql_previous_date` TIMESTAMP NULL,
            `sql_name` VARCHAR(60) DEFAULT NULL,
            `created_at` TIMESTAMP NULL,
            PRIMARY KEY (`id`)
           ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";

        foreach ($this->currentDatabases as $this->currentDatabase) {
            $db = new DbPdoMySql($this->currentDatabase);
            $db->query($query);
        }
    }

    private function logQuery()
    {
        $data = [
            "sql_author" => $this->author,
            "sql_date" => $this->extractDateFromCurrentFileName(),
            "sql_name" => $this->description,
            "created_at" => date("Y-m-d H:i:s")
        ];

        $previousSqlDate = $this->extractDateFromPreviousFileName();

        if (false !== $previousSqlDate) {
            $data["sql_previous_date"] = $previousSqlDate;
        }

        $cols = array_keys($data);

        $query = "INSERT INTO {$this->currentDatabase["name"]}.{$this->infoTable} (" . implode(",", $cols) . ") VALUES ('" . implode("','", $data) . "')";

        echo "Registrando: " . $query . EOL;

        $db = new DbPdoMySql($this->currentDatabase);
        $db->query($query);
    }

    private function runQueryByDatabase()
    {
        foreach ($this->currentDatabases as $this->currentDatabase) {
            $this->runQueryByDatabaseItem();
        }
    }

    private function moveFileToExecutedFolder()
    {
        $source = $this->currentPartFile;
        $dest = $this->currentQueueExecutedFolder . DIRECTORY_SEPARATOR . $this->currentPartFileName;
        copy($source, $dest);
        if (file_exists($dest)) {
            unlink($source);
        }
    }

    private function runQueryByDatabaseItem()
    {
        echo PHP_EOL . '--------------------------------------------------------------------' . PHP_EOL;
        echo 'DATABASE:' . $this->currentDatabase["name"] . PHP_EOL;
        echo 'FILE:' . $this->currentPartFileName . PHP_EOL;
        echo 'QUERY: ' . PHP_EOL . $this->currentQuery . PHP_EOL;
        echo 'QUERY END FOR:' . $this->currentDatabase["name"] . PHP_EOL;

        $db = new DbPdoMySql($this->currentDatabase);
        if (false !== $db->query($this->currentQuery)) {
            $this->logQuery();
        }

        echo '--------------------------------------------------------------------' . PHP_EOL;
    }

    private function mergeExecuted()
    {
        $destFile = $this->currentQueueExecutedFolder . DIRECTORY_SEPARATOR . self::MERGED_QUEUE_EXECUTED_FILENAME;
        if (file_exists($destFile)) {
            unlink($destFile);
        }

        $content = "";
        foreach ($this->getFilesFromDirSortAlphabetically($this->currentQueueExecutedFolder) as $this->currentPartFileName) {
            if ($this->currentPartFileNameIsValid() === false) {
                continue;
            }
            $this->currentPartFile = $this->currentQueueExecutedFolder . DIRECTORY_SEPARATOR . $this->currentPartFileName;
            $content .= PHP_EOL . PHP_EOL . "-- " . $this->currentPartFileName . PHP_EOL;
            $content .= file_get_contents($this->currentPartFile);
        }

        file_put_contents($destFile, $content);
    }

    private function sortAlfaber($files)
    {
        natsort($files);
    }

    private function getFilesFromDirSortAlphabetically($dirname)
    {
        $dir = dir($dirname);
        $files = [];
        while ($file = $dir->read()) {
            $files[] = $file;
        }

        natsort($files);

        $finalFiles = [];

        //Regerar indices
        foreach ($files as $finalFile) {

            if (array_search($finalFile, [".", ".."]) !== false) {
                continue;
            }

            $finalFiles[] = $finalFile;
        }

        return $finalFiles;
    }

    public function newQuery()
    {
        $filename = date("Y.m.d.H.i.s") . ".sql";
        $datetimeRow = "-- " . date("Y-m-d H:i:s") . EOL;



        foreach ($this->configs as $this->currentConfig) {
            $originDir = realpath($this->currentConfig['mainfolder']);

            //echo $this->currentConfig['mainfolder'] . "|" . $originDir . EOL . EOL;

            if (!is_writable($originDir)) {
                throw new \Exception("Not exists or not writeable: (" . $originDir . ")");
            }

            $newFile = $originDir . DS . $filename;

            if (file_exists($newFile)) {
                throw new \Exception("Arquivo existe!" . $newFile);
            }

            file_put_contents($newFile, $datetimeRow);

            echo $newFile . EOL;
        }
    }

    private function getPreviousFilename($filename = null)
    {
        $mainFolderPath = $this->getMainFolderFromCurrentConfig();
        $allFiles = $this->getFilesFromDirSortAlphabetically($mainFolderPath);
        foreach ($allFiles as $key => $mainFolderFilename) {
            echo $key . " " . $mainFolderFilename . EOL;
            //continue;
            $pKey = $key - 1;
            if ($filename === $mainFolderFilename && isset($allFiles[$pKey])) {
                return $allFiles[$pKey];
            }
        }
        return false;
    }

    public function reset()
    {
        foreach ($this->configs as $this->currentConfig) {

            $this->currentDatabases = $this->currentConfig['databases'];

            $originDir = $this->getMainFolderFromCurrentConfig();
            $destinationDir = realpath($this->currentConfig['queuefoldername'] . "/queue");
            $destinationExecutedDir = realpath($this->currentConfig['queuefoldername'] . "/queue/executed");

            array_map('unlink', glob($originDir . "/*.sql*"));
            array_map('unlink', glob($destinationDir . "/*.sql*"));
            array_map('unlink', glob($destinationExecutedDir . "/*.sql*"));

            foreach ($this->currentDatabases as $this->currentDatabase) {
                $db = new DbPdoMySql($this->currentDatabase);
                $db->query("DROP TABLE IF EXISTS `{$this->infoTable}`");
                echo "Removendo tabela: " . $this->currentDatabase["name"] . "." . $this->infoTable . EOL;
            }

            echo "Limpando diretório: " . $originDir . EOL;
            echo "Limpando diretório: " . $destinationDir . EOL;
            echo "Limpando diretório: " . $destinationExecutedDir . EOL;

            $this->createInfoTablesIfNotExists();
        }
    }

}
