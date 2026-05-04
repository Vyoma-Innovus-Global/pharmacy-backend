<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$db_name	=	'pharmacy_portal';
$host	=	'172.25.144.32';
$db_user	=	'postgres';
$db_pass	=	'postgres';
$port_num	=	'5432';
// === PostgreSQL Database Connection ===
$dbconn = pg_connect("host=$host dbname=$db_name port=$port_num user=$db_user password=$db_pass");
if (!$dbconn) {
    die("Error in connection: " . pg_last_error());
}
echo "db connected to $db_name <br/>" ; 
