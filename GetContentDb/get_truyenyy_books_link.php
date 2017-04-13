<?php
/**
 * Created by PhpStorm.
 * User: Mr Cuong
 * Date: 4/10/2017
 * Time: 8:34 PM
 */

require '../libs/simple_html_dom.php';
require '../config.php';
require '../functions.php';

$base_url = 'http://truyenyy.com';
$_url_list_books = 'http://truyenyy.com/danhmuctruyen/?loai_truyen=all&the_loai=all&sap_xep=alphabet&page=';

try {
    $conn = getConnecDatabase();

    for($p = 3; $p < 64; $p++) {
        echo '<h4>PAGE ' . $p . '</h4>';
        $url = $_url_list_books . $p;
        $html = file_get_html($url);
        foreach($html->find('.name_list') as $element) {
            foreach($element->find('a') as $a) {
                insert_new_book($conn, $a->plaintext, $a->href);
            }
        }
    }
} catch (PDOException $e) {
    //file_put_contents('PDOErrors.txt', $e->getMessage(), FILE_APPEND);
    echo '<br/>Error DB: ' . $e->getMessage();
}

/**
 * Function insert book to db;
 *
 * @param $conn The Database connection;
 * @param $_name The book name
 * @param $_source_ink
 */
function insert_new_book($conn, $_name, $_source_ink) {
    //$conn = getConnecDatabase();
    $query = $conn->prepare("INSERT INTO wt_book (name, source_link) VALUES (:name, :source_link)");
    $query->execute(array(
        ':name' => $_name,
        ':source_link' => $_source_ink
    ));
    echo '<br/>SUCCESS : add book name : ' . $_name;
}