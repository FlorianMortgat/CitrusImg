<?php
# chemin d’accès à la base de données (SQLite)
define('DB_PATH', dirname(__DIR__) . '/data/citrusimg.sqlitedb');

# répertoire contenant les images elles-mêmes
define('IMG_DIR', dirname(__DIR__) . '/img');

# répertoire contenant les définitions des tables
define('SQL_DIR', dirname(__DIR__) . '/sql');

# URL racine de l'application (doit pointer sur htdocs)
define('URL_ROOT', 'votre-domaine.com');
