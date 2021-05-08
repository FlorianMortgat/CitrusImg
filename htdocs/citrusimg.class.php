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
}
