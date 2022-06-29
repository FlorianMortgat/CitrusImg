<?php

/**
 * Classe d’interface avec le stockage en BDD des méta-données des images
 * sauvegardées
 * 
 * TODO: comme la classe s’appelle ImageStorage, ce serait logique que ce
 * soit elle qui soit responsable de la sauvegarde des fichiers images sur
 * le disque dur.
 * 
 * TODO: prévoir un mécanisme par lequel la base de données peut évoluer
 * dans sa structure (ajout suppression de colonnes etc.)
 */
class ImageStorage extends PDO {
	/**
	 * 
	 */
	const version = '0.2.0';
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
		} elseif (!$this->isDbUpToDate()) {
			$this->upgrade();
		}
	}

	function isDBUpToDate()
	{
		// 1) test if the config table exists. If not, version is assumed to be
		//    0.1.0
		$installedVersion = "0.1.0";

		$ps = $this->query('SELECT name FROM sqlite_master WHERE type="table" AND name = "Config"');
		if (false !== $ps->fetch(PDO::FETCH_OBJ)) {
			// table "config" exists
			$ps = $this->query('SELECT value FROM config WHERE name = "installed_version"');
			if ($obj = $ps->fetch(PDO::FETCH_OBJ)) {
				// table "config" has a conf named "installed_version"
				$installedVersion = $obj->installed_version;
			}
		}
		
		// find directories matching version upgrades
		$directories = array_filter(
			glob(SQL_DIR . '/upgrades/*', GLOB_ONLYDIR),
			function ($dirname) use ($installedVersion) {
				$dirname = basename($dirname);
				return (
					// directory name must look like a version number (X.Y)
					preg_match('/^\d+\.\d+$/', $dirname)
					&&
					// directory version must be (strictly) higher than installed
					version_compare($dirname, $installedVersion) > 0
				);
			}
		);
		usort($directories, 'version_compare');
		foreach ($directories as $directory) {
			$versionJsonFile = $directory . '/' . basename($directory) . '.json';
			$versionJson = json_decode(
				file_get_contents($versionJsonFile, false, null, 0, 65535),
				true
			);
			
		}


		// 3) find upgrade scripts
		// 4) run upgrade scripts
		// 5) write new version if no errors occurred
	}

	function upgrade()
	{

	}

	/**
	 * Creates a new record for an image's metadata
	 *
	 * @param array $imgData
	 */
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

	/**
	 * Retrieves a record for an image's metadata
	 * 
	 * @param string $imgid
	 */
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

	/**
	 * Retrieves the last $nb image metadata records
	 * 
	 * @param int $nb
	 */
	function getLast(int $nb=10)
	{
		$sql = 'SELECT rowid, imgid, mime, author, description, license, orig_name, dateposted FROM Pic ORDER BY rowid DESC LIMIT ?';
		$ps = $this->prepare($sql);
		$ps->execute([$nb]);
		return $ps->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Returns true if the $imgid already exists in the database.
	 * 
	 * @param string $imgid
	 */
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
	 * Returns the singleton instance
	 * @return ImageStorage
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
	 * Creates the database tables.
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

	private function getCurrentVersion()
	{

	}

	/**
	 * At startup, if the application detects a version upgrade, it
	 * checks for new queries to run and runs them.
	 */
	private function upgrade()
	{
		$sql = 'SELECT name FROM sqlite_master WHERE type="table" AND name="Config"';
		$ps = $this->prepare($sql);
		$ps->execute();
		return $ps->fetch_all();
	}
}
