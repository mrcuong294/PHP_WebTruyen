<?php
/**
 * Created by PhpStorm.
 * User: pc
 * Date: 4/13/2017
 * Time: 2:23 PM
 */
require '../libs/simple_html_dom.php';
require '../config.php';
require '../functions.php';

// key secret appen file name;
define('SECRET_KEY', 'knjg');

try {
    $conn = getConnecDatabase();
    $query = $conn->prepare("SELECT id, id_book, number, source_link FROM wt_chapter 
              WHERE source_link IS NOT NULL AND source_link<>'' LIMIT 1");
    $query->execute();

    if($query->rowCount() > 0) {
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $content = get_content_chap($row['source_link']);
            $file_name = $row['id_book'] . '_' . $row['number'] . '_' . $row['id'] . SECRET_KEY;
            if ($content != null) {
                write_file_chapter($file_name, $content);
                echo '<br/>DONE save file: ' . $file_name;
            } else {
                echo '<br/>FAILED save file: ' . $file_name;
            }
        }
    }
} catch (PDOException $e) {
    //file_put_contents('PDOErrors.txt', $e->getMessage(), FILE_APPEND);
    echo '<br/>Error DB: ' . $e->getMessage();
}

function get_content_chap($link) {
    $pageData = curl($link);
    if ($pageData != null) {
        $html = new simple_html_dom();
        $html->load($pageData);
        $noidung = $html->find('#id_noidung_chuong', 0);
        if ($noidung != null) {
            return $noidung->innertext;
        }
    }
    return null;
}