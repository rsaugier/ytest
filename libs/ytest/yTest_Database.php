<?php

class yTest_Database {
    // Fields
    ////////////////////////////////////////////////////////////

    private $pdos = null;

    private $initialized = false;

    // Singleton pattern
    ////////////////////////////////////////////////////////////

    // Singleton getter
    public static function instance() {
        static $inst = null;
        if ($inst === null) {
            $inst = new yTest_Database();
        }
        return $inst;
    }

    // Private singleton constructor
    private function __construct() {
        try {
            $this->pdos = array();
            eval('$servers = '.self::getServers().';');
            foreach ($servers as $server) {
                $pdo = new PDO('mysql:host=' . $server . ';dbname=' . self::getDatabaseName(),
                    self::getLogin(), self::getPassword());
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->pdos[] = $pdo;
            }
        } catch (Exception $ex) {
            yTest_error("yTest_Database::__construct() failed, with exception:", $ex);
        }
    }

    // Other static methods
    ////////////////////////////////////////////////////////////


    public static function getLogin() {
        return YTEST_LOGIN;
    }

    public static function getPassword() {
        return YTEST_PASSWORD;
    }

    public static function getServers() {
        return YTEST_SERVERS;
    }

    public static function getDatabaseName() {
        return YTEST_DBNAME;
    }

#endif

    // Instance methods
    ////////////////////////////////////////////////////////////

    public function runScript($sqlScriptContents) {
        $queries = preg_split('/;\s*$/m', $sqlScriptContents);
        foreach ($queries as $query) {
            $q = trim($query);
            if (strlen($q) > 0) {
                $this->runQuery($q);
            }
        }
        return true;
    }

    public function runQuery($query, $serverId = null) {
        if ( ! $this->isInitialized() ) {
            $this->initialize();
        }

        if ($serverId === null) {
            foreach ($this->pdos as $serverIdx => $pdo) {
                $res = $this->runQuery($query, $serverIdx);
            }
            return $res;
        }

        try {
            $res = $this->pdos[$serverId]->query($query);
        } catch (PDOException $ex) {
            $res = false;
        }

        if ($res === false) {
            throw yTest_Exception::sqlError($query, $ex);
        }

        return $res;
    }

    public function getTableNames($serverIdx = 0) {
        $query = 'SHOW TABLES';
        $statement = $this->runQuery($query, $serverIdx);
        $tableNames = array();
        while (($tableName = $statement->fetchColumn(0))) {
            $tableNames[] = $tableName;
        }

        return $tableNames;
    }

    public function deleteAllTables() {
        foreach ($this->pdos as $serverIdx => $pdo) {
            $names = $this->getTableNames($serverIdx);
            foreach ($names as $name) {
                $query = "DROP TABLE IF EXISTS `" . $name . "`";
                $this->runQuery($query, $serverIdx);
            }
        }
    }

    public function truncateTable($tableName) {
        $query = "TRUNCATE TABLE `" . $tableName . "`";
        $this->runQuery($query);
    }

    public function initialize() {
        if ( $this->initialized ) {
            return;
        }
        $this->initialized = true;
        try {
            $this->deleteAllTables();
        } catch (Exception $ex) {
            yTest_error("yTest_Database::initialize() failed, with exception:", $ex);
        }
    }

    public function isInitialized() {
        return $this->initialized;
    }

};
