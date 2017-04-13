<?php
/**
 * Created by PhpStorm.
 * User: Mr Cuong
 * Date: 4/10/2017
 * Time: 8:19 PM
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'webtruyen_yy');
define('DB_USER', 'root');
define('DB_PASS', '');

function getConnecDatabase() {
    try {
        $conn = new PDO('mysql:host=' . DB_HOST .';dbname=' . DB_NAME .';charset=utf8', DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        file_put_contents('PDOErrors.txt', $e->getMessage(), FILE_APPEND);
        return null;
    }
}