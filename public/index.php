<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../src/Auth.php';
require_once __DIR__.'/../src/Security.php';
Auth::requireLogin();
?>
<!doctype html><meta charset="utf-8"><title>Home</title>
<h1>Welkom!</h1>
<p>Je bent ingelogd. (Lijsten bouwen we in de volgende stap.)</p>
<p><a href="logout.php">Uitloggen</a></p>
