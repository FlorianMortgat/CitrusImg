<?php

/**
TODO
 - réorganiser la production du code HTML (template) et l’indentation en général
 - ajouter quelques fonctionnalités
 - voir comment ça tourne et aviser
*/

include 'config.php';
include 'common.inc.php';
include 'citrusimg.lib.php';
include 'citrusimg.class.php';

define('RESIZE_THRESHOLD', 0);

/**
 * Returns an array with some storage (available/used) info
 * 
 * @return array
 */
function getDiskStats() {
	static $diskStats = null;
	// espace disque maximum alloué pour le service : 15 Go
	$duMaxService = DISK_USAGE_QUOTA;

	// espace disque en deçà duquel on bloque tout : 5 Go
	$dfMinServer = DISK_USAGE_ALERT_THRESHOLD;

	// espace disque total restant sur le serveur
	$dfServer = disk_free_space(IMG_DIR);

	// espace disque occupé par le service (DB, IMG, total)
	// TODO (?) éviter d’utiliser scandir quand il y aura beaucoup
	// d'images… idéalement, il faudrait faire un répertoire par mois
	// l’avantage étant que ça pourrait s’appliquer rétrospectivement
	$duDB = is_file(DB_PATH) ? filesize(DB_PATH) : 0;
	$duIMG = array_reduce(
		scandir(IMG_DIR),
		function ($c, $f) {
			return $c + (filesize(IMG_DIR . "/$f") ?: 0);
		},
		0
	);
	$duService = $duDB + $duIMG;

	// espace disque disponible restant pour le service
	// = combien d’espace disque puis-je consommer avant d’arriver soit à mon
	// quota, soit à ce qu’il ne reste plus que le minimum acceptable sur mon
	// serveur ?
	$dfService = max(
		// une valeur négative n’aurait aucun sens
		0,
		min(
			// 
			($dfServer - $dfMinServer),
			// 
			$duMaxService - $duService
		)
	);
	$duPercent = $dfService ? ($duService / $duMaxService) * 100 : 100;

	if ($diskStats === null) {
		$diskStats = [
			'duMaxService' => $duMaxService,
			'dfMinServer' => $dfMinServer,
			'dfServer' => $dfServer,
			'duDB' => $duDB,
			'duIMG' => $duIMG,
			'duService' => $duService,
			'dfService' => $dfService,
			'duPercent' => $duPercent,
		];
	}
	return $diskStats;
}

/**
 * Returns the About section and the Rules section
 * 
 * @return string
 */
function getAboutAndRulesSections() {
	ob_start();
	?>
		<h1>Hébergement d’images pour agrumes-passion.com</h1>
			<details id="">
				<summary> <h2>Règles d’utilisation</h2> </summary>
				<ol>
					<li>Uniquement pour partager sur le forum agrumes-passion.com.
						Si l’image a été partagée sur agrumes-passion.com, vous pouvez 
						éventuellement la partager ailleurs aussi, par exemple sur le
						forum américain (citrusgrowersv2.proboards.com)</li>
					<li>Vous êtes l’auteur de l’image ou bien vous avez le droit de la diffuser</li>
					<li>Par défaut, vous placez vos images sous licence CC-BY-NC.</li>
					<li>Formats acceptés : PNG, JPG, GIF, SVG, WEBP</li>
					<li>Vous avez conscience de ne pas être la seule personne à utiliser le service et
						restez sobre sur la quantité d’images envoyées</li>
					<li>Vous utilisez le service de bonne foi et ne cherchez pas à le détourner de son
						but initial (le partage de connaissances sur les agrumes entre passionnés)</li>
				</ol>
			</details>
			<details>
				<summary><h2>À propos</h2></summary>
				<p>
					Je suis le membre dont le pseudonyme est DNoyau sur
					 <a href="https://agrumes-passion.com">agrumes-passion.com</a>.
				</p>
				<p>Mon but est de permettre aux membres du forum de partager leurs images sans difficulté
					et sans risquer qu’elles disparaissent subitement au bout de quelques mois. Je trouve
					toujours ça dommage quand on tombe sur un sujet du type « guide visuel pour reconnaître
					les carences » et qu’il n’y a plus aucune illustration.
				</p>
				<p>
				J’offre ce service gratuitement et je souhaite y consacrer une quantité limitée de
				ressources, en particulier d’espace disque. Je paye un loyer raisonnable pour le serveur,
				dont je me sers pour plusieurs projets personnels, pas uniquement pour cet hébergement
				d’images. N’allez pas vous sentir coupable ou penser que je fais un gros effort financier :
				le plus gros effort a été la programmation du service, mais c’est aussi amusant.
				</p>
				<p>
				J’ai réservé 15 gigaoctets pour ce service, ce qui est très peu par rapport aux
				hébergeurs classiques (si 50 utilisateurs mettent 5 images par mois de 1 Mo chacune, ce
				sera saturé en 5 ans environ). Pour cette raison, la taille des images sera réduite,
				sauf si vous cochez la case "qualité élevée".
				</p>
				<p>
				J’augmenterai peut-être ce quota si je constate que c’est utile.
				</p>
				<p>
				Sauf gros accident, je souhaite maintenir mon service au moins 10 ans. Mon but est que les
				membres puissent héberger facilement les images et que ces images ne disparaissent
				pas subitement au bout d’un an ou deux.
				</p>
				<p>
				L’autre avantage est que je peux intervenir manuellement si jamais un problème
				survient. Je peux notamment supprimer une image qui ne correspondrait pas aux règles et
				faire évoluer le programme.
				</p>
				<p>
				Sur l’espace alloué au service, sont encore disponibles : %s (utilisés : %s, soit
				%.02f&percnt;)
				</p>
			</details>
	<?php

	$diskStats = getDiskStats();
	$ret = sprintf(
		ob_get_clean(),
		humanStorageSize($diskStats['dfService']),
		humanStorageSize($diskStats['duService']),
		100 * $diskStats['duService'] / max(1, DISK_USAGE_QUOTA)
	);
	return $ret;
}

/**
 * Returns the image posting form
 * @return string
 */
function getForm() {
	ob_start();
	echo getAboutAndRulesSections();
	?>
	<form enctype="multipart/form-data" action="" method="POST">
		<!-- MAX_FILE_SIZE must precede the file input field -->
		<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo 8 * 1024**2; ?>" />
		<table>
			<colgroup>
				<col class="c0" />
				<col class="c1" />
			</colgroup>
			<tbody>
				<tr>
					<td>
						<label for="imgfiles[]">Choisissez une ou plusieurs images à envoyer :</label>
					</td>
					<td>
						<input name="imgfiles[]"
							type="file"
							accept="image/png, image/jpeg, image/gif, image/svg, image/webp"
							multiple="multiple"
							class="fileinput"
							required
						/>
					</td>
				</tr>
				<tr>
					<td>
						<label for="author">Votre pseudo (optionnel) :</label>
					</td>
					<td>
						<input name="author" type="text" />
					</td>
				</tr>
				<tr>
					<td>
						<label for="description">Une description (optionnelle) :</label>
					</td>
					<td>
						<textarea name="description"></textarea>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<details class="uploadoptions">
							<summary class="">Options supplémentaires</summary>
							<table class="">
								<colgroup>
									<col class="c10" />
									<col class="c11" />
								</colgroup>
								<tbody>
									<tr>
										<td>
											<label for="preservequality"
												title="Si votre photo est exceptionnelle, pourquoi ne pas la mettre sur Wikimedia Commons ?"
											>
											Garder la qualité d’origine si possible
											</label>
										</td>
										<td>
											<input type="checkbox" name="preservequality">
										</td>
									</tr>
									<tr>
										<td>
											<label for="license">Licence : peut-on réutiliser votre photo ailleurs ?</label>
										</td>
										<td>
											<blockquote id="licenseHelp"></blockquote>
											<datalist id="licenses">
												<option id="CC0" value="CC0"></option>
												<option id="CCBY" value="CC BY"></option>
												<option id="CCBYNC" value="CC BY-NC"></option>
												<option id="CCBYSA" value="CC BY-SA"></option>
												<option id="CCBYNCSA" value="CC BY-NC-SA"></option>
												<option id="APSITE" value="AP+SITE"></option>
											</datalist>	
											<input name="license" type="text" list="licenses" placeholder="CC BY" />
										</td>
									</tr>
								</tbody>
							</table>
						</details>
					</td>
				</tr>
			</tbody>
		</table>
		<!-- Name of input element determines name in $_FILES array -->
	
		<button name="action" value="send" class="submit" >Envoyer l’image</button>
	</form>

	<?php
	return sprintf(
		ob_get_clean()
	);
}

/**
 * Puts the page template (<html> and such) around $contents
 * and returns it.
 * @param string $contents
 * @return string
 */
function getHTMLPage($content) {
	ob_start();
	?>
	<html>
		<head>
			<link rel="stylesheet" type="text/css" href="style.css"/>
			<script type="application/javascript" src="js/citrusimg.lib.js"></script>
			<meta charset="utf-8" />
			<title>CitrusImg</title>
		</head>
		<body>
			<div class="banner"></div>
			<div id="content">%s</div>
		</body>
	</html>
	<?php
	return sprintf(
		ob_get_clean(),
		$content
	);
}

/**
 * Prints out the image file corresponding to the `imgid` query parameter.
 * @return void
 */
function action_get_image() {
	/*
	1) chercher l’image dans la bdd
	2) récupérer le nom du fichier depuis la bdd
	3) envoyer les headers adéquats
	4) envoyer l’image
	*/

	$db = ImageStorage::getDB();
	$imgid = getVal('imgid', 0);
	if (!preg_match('/^[\w+-]+$/', $imgid)) {
		throw new Exception("Might be an attack.");
	}
	$imgData = $db->getImageData($imgid);
	if (!$imgData) {
		throw new Exception("Image data for imgid=$imgid not found.");
	}
	if (!is_file($imgData['path'])) {
		throw new Exception(sprintf("File not found: %s.", $imgData['path']));
	}
    $orig_name = $imgData['orig_name'];
    $orig_name = preg_replace('/[\/"\\\]/', '', $orig_name);
    if (empty($orig_name)) {
        $orig_name = 'photo';
    }
	header('Content-Type: ' . $imgData['mime']);
	header('Cache-Control: public, max-age=31557600');
	header('Content-Length: ' . filesize($imgData['path']));
    header(sprintf('Content-Disposition: inline; filename="%s"', $orig_name));
	echo file_get_contents($imgData['path']);
}

/**
 * Saves the image file sent with the HTTP request, resizes it if needed and
 * stores its metadata into the database, then prints out a page where the
 * user can see their image and get the BBCode for including it in a forum.
 */
function action_send() {
	/*
	1) vérifier la taille
	2) vérifier le type
	3) générer un nom "aléatoire" (réessayer tant que le nom existe déjà)
	4) si image volumineuse et option "grande image" non cochée, on convertit
	   avec imagemagick
	5) on stocke l’image
	6) TODO: on met un cookie 'auteur'
	*/
	$diskStats = getDiskStats();
	if ($diskStats['dfServer'] < 5* 1024**3) {
		echo getHTMLPage('Plus d’espace disque.');
		exit;
	}
	if ($diskStats['duDB'] + $diskStats['duIMG'] >= $diskStats['duMaxService']) {
		echo getHTMLPage('Quota dépassé.');
		exit;
	}

	$preservequality = postVal('preservequality') === 'on';
	$db = ImageStorage::getDB();
	$commonImgData = [
		'author' => postVal('author'),
		'description' => postVal('description'),
		'license' => postVal('license'),
		'dateposted' => date('Y-m-d H:i:s'),
	];
	$ret = [];

	$F = repackFiles('imgfiles');
	foreach ($F as $f) {
		if (empty($f['tmp_name'])) {
			echo 'failure with ', $f['name'], "\n";
			continue;
		}
		$imgid = generateId(); while ($db->hasId($imgid)) { $imgid = generateId(); }
		$newPath = IMG_DIR . '/' . $imgid;		
		if (filesize($f['tmp_name']) === 0) {
			throw new Exception('uploaded file is empty');
		}
		if (!move_uploaded_file($f['tmp_name'], $newPath)) {
			throw new Exception('unable do write to ' . $newPath);
		}
		if (filesize($newPath) > RESIZE_THRESHOLD && empty($preservequality)) {
			$imgck = new Imagick();
			$imgck->readImage($newPath);
			$w = $imgck->getImageWidth();
			$h = $imgck->getImageHeight();
			$imgck->resizeImage(
				min($w, 700),
				min($h, 900),
				imagick::FILTER_CATROM,
				0.7,
				true
			);
			$imgck->writeImage($newPath);
			$imgck->clear();
			$imgck->destroy();
		}
		$imgData = [
			'imgid' => $imgid,
			'mime' => $f['type'],
			'path' => $newPath,
            'orig_name' => $f['name'],
		] + $commonImgData;

		$ret[] = getImagePreviewAndBBCode($imgData);	

		$db->storeImageData($imgData);
	}
	$pageContent = '';
	$pageContent .= implode("\n", $ret);

	echo getHTMLPage($pageContent);
}

/**
 * Returns a HTML div containing the image corresponding to $imgData
 * and a textarea with the BBCode for copy-pasting onto a forum.
 * 
 * @param array $imgData
 * @return string
 */
function getImagePreviewAndBBCode($imgData) {
	ob_start();
	?>
	<div class="image-bb-preview" style="margin-bottom: 6em;">
		<p>
		Pour insérer l’image, copiez ce code et collez-le directement sur le
		forum, sans cliquer sur le bouton « image ». Ne perdez pas ce code !
		</p>
		<p><textarea>[img]%s[/img]</textarea></p>
		<p>
			<img class="forum-preview" src="%s" style="max-width: 600px;" />
			<br/>
			<a href="%s://%s/index.php">Retour à l’accueil</a>
		</p>
	</div>
	<?php
	return sprintf(
		ob_get_clean(),
		getImgURL($imgData['imgid']),
		getImgURL($imgData['imgid']),
		$_SERVER['REQUEST_SCHEME'],
		$_SERVER['HTTP_HOST']
	);
}

/**
 * Returns true if the available disk space on the hosting platform is less than
 * the alert threshold (@see config.php)
 * 
 * @return bool
 */
function isDiskNearlyFull() {
	$diskStats = getDiskStats();
	return $diskStats['dfServer'] < DISK_USAGE_ALERT_THRESHOLD;
}

/**
 * Returns true if the application (including database and images, but not
 * counting the source code and assets) takes up more storage space than the
 * allotted quota (@see config.php)
 * 
 * @return bool
 */
function isQuotaExceeded() {
	$diskStats = getDiskStats();
	return $diskStats['duDB'] + $diskStats['duIMG'] >= DISK_USAGE_QUOTA;
}

/**
 * Handles all GET queries except queries with an `action` parameter if there
 * is a function called `action_XYZ` (where XYZ is the value of `action`).
 * @TODO: move it to common.inc.php and make a default action handler for
 *        consistency
 *
 * @return void
 */
function handle_get() {
	$diskStats = getDiskStats();
	$content = '';
	$showForm = true;
	if (isDiskNearlyFull()) {
		$showForm = false;
		$content = 'Le service n’accepte plus d’images jusqu’à nouvel ordre car'
		.' le disque dur du serveur est presque plein.';
	}
	if (isQuotaExceeded()) {
		$showForm = false;
		$content = 'Le service n’accepte plus d’images jusqu’à nouvel ordre car'
		.' la taille totale des images hébergées et de la base de données'
		.' dépasse le quota maximum.';
	}
	if ($showForm) {
		$content .= getForm();
	}
	$content .= getLastN();
	echo getHTMLPage($content);
}

/**
 * Returns a HTML section with a gallery of the last 10 uploaded images along
 * with their metadata.
 * 
 * @return string
 */
function getLastN() {
	/*
	1) récupère les ID des 10 dernières images ajoutées
	2) affiche les 10 dernières images
	*/
	ob_start();
	$n = intval(getVal('showlastn', 10));
	$n = min(500, $n);
	$db = ImageStorage::GetDB();
	$imgDatas = $db->getLast($n);
	$count = count($imgDatas);
	?>
	<h2>Les %d dernières images</h2>
	<section class="gallery">
		<?php
		foreach ($imgDatas as $imgData) {
			echo str_replace('%', '%%', getImgInfoBox($imgData));
		}
		?>
	</section>
	<?php
	return sprintf(ob_get_clean(), $count);
}

/**
 * Returns a random ID meant to be unique (collisions are possible, but very
 * unlikely)
 * 
 * @return string
 */
function generateId() {
	return uniqid();
}

handle_requests();
