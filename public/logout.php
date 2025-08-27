<?php
// Log de gebruiker uit en stuur door naar de loginpagina.
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::logout(); // sessie leegmaken + cookie ongeldig
header('Location: /todo/public/login.php'); // redirect naar login