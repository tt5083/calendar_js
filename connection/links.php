<?php
require_once __DIR__ . "/../../web249/connection/linkcfg.php";
//權限 A141 預算管理系統
define("SYSID", "A141_");
define("cardb", "a141");
define("SYSNAMES", "預算管理系統");

define("normal_user", 1);
define("finance", 5);
define("budget_locker", 6);
define("admin", 9);

//HEADER
header('Content-Type: text/html; charset=utf-8');
if (!isset(${S})) {
    session_start();
}
function db_connect()
{
    $dsn = "mysql:host=localhost; port=3306; dbname=" . cardb . "; charset=utf8";
    $conn = new PDO($dsn, dbuser, dbpwd);
    $conn->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES utf8');
    return $conn;
}

${D} = db_connect();
