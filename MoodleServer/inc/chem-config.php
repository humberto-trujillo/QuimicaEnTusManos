<?php

if (isset($_SERVER['SCRIPT_FILENAME']) && 'chem-config.php' == basename($_SERVER['SCRIPT_FILENAME']))
{
	require_once("404.php");
	die();
}
	
// Configuración de la base de datos
const DB_HOST = 'localhost';
const DB_USER = 'pokemew_chem';
const DB_PASS = 'O?tI?M%CX4za';
const DB_NAME = 'pokemew_chem';

// Conexión a la base de datos
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Verificar conexión
if ($mysqli->connect_errno) 
{
	echo "<p>MySQL error no {$mysqli->connect_errno} : {$mysqli->connect_error}</p>";
	die();
}
?>