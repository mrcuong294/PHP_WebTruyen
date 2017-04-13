<?php set_time_limit(0);
/**
 * Created by PhpStorm.
 * User: Mr Cuong
 * Date: 4/11/2017
 * Time: 9:32 PM
 */
require '../libs/simple_html_dom.php';
require '../config.php';
require '../functions.php';

$base_url = 'http://sstruyen.com';
try {
    $conn = getConnecDatabase();
    $query_get = $conn->prepare("SELECT id, source_link FROM wt_book LIMIT 7594, 6000");
    $query_get->execute();

    if($query_get->rowCount() > 0) {
        while ($row = $query_get->fetch(PDO::FETCH_ASSOC)) {
            echo '<br /><br />==== Book ID : ' .$row['id'] . '====<br/>';
            $_html = file_get_html($base_url . $row['source_link']);
            //add chapter on first pager;
            addChapterData($conn, $row['id'], $_html);

            // add chapter pager next;
            $pagerData = getPagerData($_html);
            if($pagerData != null) {
                for($i = 1; $i < $pagerData['total']; $i++) {
                    echo '<br/><br/>Page = ' . $i . '<br/>';
                    $link = $pagerData['link'] . $i . $pagerData['ext'];
                    $_htmlx = file_get_html($link);
                    addChapterData($conn, $row['id'], $_htmlx);
                }
            }
        }
    }
} catch (PDOException $e) {
    //file_put_contents('PDOErrors.txt', $e->getMessage(), FILE_APPEND);
    echo '<br/>Error DB: ' . $e->getMessage();
}

function getPagerData($_html) {
    $base_url = 'http://sstruyen.com';
    $pageData = null;

    $page_split = $_html->find('.page-split',0);
    //var_dump($page_split);
    if($page_split != null) {
        $tags_a = $page_split->find('a');
        $link = $base_url . $tags_a[0]->href;
        $links = explode('page-', $link);
        $pageData['link'] = $links[0] . 'page-';
        $pageData['ext'] = '.html#chaplist';
        $pageData['total'] = (int)$tags_a[sizeof($tags_a)-1]->plaintext;
    }
    return $pageData;
}

function addChapterData($conn, $book_id, $html) {
    $chapter = null;

    $chuongmois = $html->find('.chuongmoi');
    $chuongmoi = $chuongmois[0];
    if(sizeof($chuongmois) > 1) {
        $chuongmoi = $chuongmois[1];
    }
    foreach($chuongmoi->find('a') as $a) {
        $link = $a->href;
        $name = $a->title;
        $num = $a->find('div',0)->plaintext;
        //echo '<br />Book ID : ' .$book_id . ' number = ' . $num;
        insertChapter($conn, $book_id, $num, $name, $link);
    }
}

function insertChapter($conn, $book_id, $number, $name, $source_link) {
    //$conn = getConnecDatabase();
    $query = $conn->prepare("INSERT INTO wt_chapter (id_book, name, number, source_link) VALUES (:id_book, :name, :number, :source_link)");
    $success = $query->execute(array(
        ':id_book' => $book_id,
        ':name' => $name,
        ':number' => $number,
        ':source_link' => $source_link
    ));
    if($success){
        echo '<br/>SUCCESS : add chapter book ID : ' . $book_id . ' | chap number' . $number;
    } else{
        echo '<br/>FALSE : add chapter book ID : ' . $book_id . ' | chap number' . $number;
    }
}