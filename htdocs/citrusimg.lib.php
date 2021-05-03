<?php
/**
 * Reorders one value from the $_FILES array.
 */
function repackFiles($name) {
	$F = $_FILES[$name];
	$ret = [];
	for ($i = 0; $i < count($F['name']); $i++) {
		$f = [];
		foreach ($F as $k => $v) { $f[$k] = $v[$i]; }
		$ret[] = $f;
	}
	return $ret;
}


function getImgURL($imgid) {
	return sprintf(
		'%s://%s/index.php?action=get_image&imgid=%s',
		$_SERVER['REQUEST_SCHEME'],
		$_SERVER['HTTP_HOST'],
        $imgid
    );
}

function cleanImgData(&$imgData) {
    foreach (['author', 'description', 'license', 'orig_name'] as $k) {
        $imgData[$k] = htmlspecialchars($imgData[$k]);
    }
}

function getImgInfoBox($imgData) {
    ob_start();
    ?>
    <div class="img-info-box">
        <dl>
            <dt><span>Auteur</span></dt>
            <dd>%s</dd>
    
            <dt><span>Date</span></dt>
            <dd>%s</dd>

            <dt><span>Description</span></dt>
            <dd>%s</dd>

            <!--
            <dt>Licence</dt>
            <dd>%s</dd>
            -->


        </dl>
        <div>
        <a href="%s" target="_blank"><img src="%s" /></a>
        </div>
        
    </div>
    <?php
    cleanImgData($imgData);
    return sprintf(
        ob_get_clean(),
        $imgData['author'] ?: 'anonyme',
        $imgData['dateposted'],
        $imgData['description'] ?: 'pas de description',
        $imgData['license'] ?: 'non définie',
        getImgURL($imgData['imgid']),
        getImgURL($imgData['imgid'])
    );
}

// https://gist.github.com/liunian/9338301  < MrCaspan >
function humanStorageSize($bytes) {
    $i = floor(log($bytes, 1024));
    return frnum(round($bytes / 1024 ** $i, [0,0,2,2,3][$i]) . ' ' . ['o','Ko','Mo','Go','To'][$i]);
}

function frnum($num) {
    return str_replace('.', ',', $num);
}

// function getDB() {
//     static $db = null;
//     $isDbInitialized = file_exists(DB_PATH);
//     if ($db === null) {
//         $db = new PDO('sqlite:' . DB_PATH);
//     }
//     if (!$isDbInitialized) {

//     }
// }