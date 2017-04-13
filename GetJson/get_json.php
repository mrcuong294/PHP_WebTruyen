<?php
/**
 * Created by PhpStorm.
 * User: Mr Cuong
 * Date: 4/12/2017
 * Time: 1:40 AM
 */
require '../config.php';
require '../functions.php';

try {
    $conn = getConnecDatabase();
    getJsonFromDb($conn, 'wt_slider');
    getJsonFromDb($conn, 'wt_book');
    getJsonFromDb($conn, 'wt_chapter');
    getJsonFromDb($conn, 'wt_category');
    getJsonFromDb($conn, 'wt_type_book');
    getJsonFromDb($conn, 'wt_user');
    getJsonFromDb($conn, 'wt_client');
    getJsonFromDb($conn, 'wt_config');
} catch (PDOException $e) {
    //file_put_contents('PDOErrors.txt', $e->getMessage(), FILE_APPEND);
    echo '<br/>Error DB: ' . $e->getMessage();
}

function getJsonFromDb($conn, $tbName) {
    $query = $conn->prepare("SELECT * FROM " . $tbName . " LIMIT 1");
    $query->execute();

    if($query->rowCount() > 0) {
        $data = $query->fetch(PDO::FETCH_ASSOC);
        echo '<br /><br /> <b>========= ' . $tbName .' ==========</b><br /><br />';
        echo json_encode($data);
    }
}

function getJsonBook($conn) {
    $query = $conn->prepare("SELECT * FROM wt_book LIMIT 1");
    $query->execute();

    if($query->rowCount() > 0) {
        $data = $query->fetch(PDO::FETCH_ASSOC);

        echo json_encode($data);
    }
}