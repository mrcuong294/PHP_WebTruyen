<?php set_time_limit(0);
/**
 * Created by PhpStorm.
 * User: Mr Cuong
 * Date: 4/10/2017
 * Time: 10:04 PM
 */
require '../libs/simple_html_dom.php';
require '../config.php';
require '../functions.php';

$base_url = 'http://truyenyy.com';


try {
    $conn = getConnecDatabase();
    $query_get = $conn->prepare("SELECT id, source_link FROM wt_book LIMIT 1");
    $query_get->execute();

    if($query_get->rowCount() > 0) {
        while ($row = $query_get->fetch(PDO::FETCH_ASSOC)) {
            get_book_info($base_url . $row['source_link']);
        }
    }
} catch (PDOException $e) {
    //file_put_contents('PDOErrors.txt', $e->getMessage(), FILE_APPEND);
    echo '<br/>Error DB: ' . $e->getMessage();
}

function get_book_info($sourceLink) {
    $page_data = curl($sourceLink);
    $html = new simple_html_dom();
    $html->load($page_data);

    $_book = null;
    //get left page content;
    $_left_info = $html->find('.xfor', 0);

    $_book[':poster'] = 'http:' . $_left_info->find('img', 0)->src;
    $_tag_p_index = 0;
    foreach($_left_info->find('p') as $_tag_p) {
        switch($_tag_p_index) {
            case 0:
                $_book[':author'] = $_tag_p->find('a',0)->plaintext;
                break;
            case 1:
                $ctg = '';
                foreach( $_tag_p->find('.ds-theloai', 0)->find('span') as $_span) {
                    if(strlen($ctg) > 1) {
                        $ctg = $ctg . ' , ';
                    }
                    $ctg = $ctg . $_span->plaintext;
                }
                $_book[':category'] = $ctg;
                break;
            case 2:
                $_book[':type_book'] = $_tag_p->find('span', 1)->plaintext;
                break;
        }
        $_tag_p_index++;
    }

    var_dump($_book);
}
