<?php

class ImageStorage extends PDO {
    /**
     * 
     */
    const tables = [
        'Pic',
        //'Tag',
        //'x_Pic_Tag',
    ];


    function __construct()
    {
        $isDbInitialized = is_file(DB_PATH);
        parent::__construct('sqlite://' . DB_PATH);
        if (!$isDbInitialized) {
            $this->createTables();
        }
    }

    function storeImageData($imgData)
    {
        $sql = 'INSERT INTO Pic (imgid, mime, author, description, license, path, orig_name, dateposted)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
        $ps = $this->prepare($sql);
        return $ps->execute([
            $imgData['imgid'],
            $imgData['mime'],
            $imgData['author'],
            $imgData['description'],
            $imgData['license'],
            $imgData['path'],
            $imgData['orig_name'] ?? '',
            $imgData['dateposted']
        ]);
    }

    function getImageData(string $imgid)
    {
        $sql = 'SELECT * FROM Pic WHERE imgid = ? LIMIT 1';
        $ps = $this->prepare($sql);
        $ps->execute([$imgid]);
        if ($imgData = $ps->fetch(PDO::FETCH_ASSOC)) {
            return $imgData;
        }
        return null;
    }

    function hasId($imgid)
    {
        $sql = 'SELECT rowid FROM Pic WHERE imgid = ? LIMIT 1';
        $ps = $this->prepare($sql);
        $ps->execute([$imgid]);
        if ($ps->fetch(PDO::FETCH_OBJ)) {
            return true;
        }
        return false;
    }

    /**
     * 
     */
    public static function getDB()
    {
        // make singleton
        static $me = null;
        if ($me === null) {
            $me = new self();
        }
        return $me;
    }

    /**
     * 
     */
    private function createTables()
    {
        foreach (self::tables as $tableName) {
            $tableFile = sprintf(
                '%s/%s.table.sql',
                SQL_DIR,
                strtolower($tableName)
            );
            if (!is_file($tableFile)) {
                throw new Exception("File '$tableFile' not found.");
            }
            $queryFile = file_get_contents($tableFile);
            // delete comments
            $queryFile = preg_replace('/^\s*--.*$/m', '', $queryFile);
            // split queries (must be separated by a line containing just "----")
            $queries = explode('\n----\n', $queryFile);
            foreach ($queries as $query) {
                $this->exec($query);
            }
        }
    }
}
