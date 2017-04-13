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
    $query_get = $conn->prepare("SELECT id, source_link FROM wt_book LIMIT 2, 1000");
    $query_get->execute();

    if($query_get->rowCount() > 0) {
        while ($row = $query_get->fetch(PDO::FETCH_ASSOC)) {

            $page_data = curl($base_url . $row['source_link']);
            if ($page_data != null) {
                $html = new simple_html_dom();
                $html->load($page_data);
                if ($html != null) {
                    // Update book info
                    $_book = get_book_info($html);
                    update_book_info($conn, $row['id'], $_book);

                    echo '<br/>Insert chapter - START';
                    // Insert list chapters
                    // insert first pager;
                    get_list_chapter_on_pager($conn, $row['id'], $html);
                    // insert from pager 2;
                    $pagerTotal = get_total_pager_chapter($html);
                    if ($pagerTotal > 0) {
                        for ($p = 2; $p <= $pagerTotal; $p++) {
                            $link = $base_url . $row['source_link'] . '?page=' . $p;
                            $page_data2 = curl($link);
                            if ($page_data2 != null) {
                                $html2 = new simple_html_dom();
                                $html2->load($page_data2);
                                if ($html2 != null) {
                                    get_list_chapter_on_pager($conn, $row['id'], $html2);
                                }
                            }
                        }
                    }
                    echo '<br/>Insert chapter - END';
                }
            }
        }
    }
} catch (PDOException $e) {
    //file_put_contents('PDOErrors.txt', $e->getMessage(), FILE_APPEND);
    echo '<br/>Error DB: ' . $e->getMessage();
}

/**
 * Update book info;
 * It will call on main page;
 * @param $conn
 * @param $id
 * @param $book
 */
function update_book_info($conn, $id, $book) {
    if ($book == null) {
        return;
    }
    $book[':id'] = $id;
    $query = $conn->prepare("UPDATE wt_book SET poster=:poster,
                                                author=:author,
                                                category=:category,
                                                view=:view,
                                                total_rating = :total_rating,
                                                point_rating = :point_rating,
                                                description = :description,
                                                type_book = :type_book
                                                 WHERE id = :id");
    if($query->execute($book)) {
        echo '<br/>==========<br/>SUCCESS : update book ID : ' . $book[':id'];
    } else {
        echo '<br/>FAILURE : update book ID : ' . $book[':id'];
    }
}

/**
 * Insert chapter to db;
 * it will call on #get_list_chapter_on_pager();
 *
 * @param $conn
 * @param $book_id
 * @param $chapter
 */
function insert_chapter($conn, $chapter) {

    //$conn = getConnecDatabase();
    $query = $conn->prepare("INSERT INTO wt_chapter (id_book, name, number, source_link) 
                              VALUES (:id_book, :name, :number, :source_link)");
    $success = $query->execute($chapter);
    if (!$success) {
        echo '<br/>FAILURE : add chapter book ID : ' . $chapter[':id_book'] . ' failed at ' . $chapter['name'];
    }
    return $success;
}

/**
 * Get Book info from html code;
 * It will call on main page;
 * @param $html
 * @return null
 */
function get_book_info($html) {

    //get left page content;
    $_left_info = $html->find('.xfor', 0);
    if($_left_info == null) {
        return null;
    }
    $_book = null;

    if ($_left_info->find('img', 0) != null) {
        // get poster link;
        $_book[':poster'] = 'http:' . $_left_info->find('img', 0)->src;
    }

    $_tag_p_index = 0;
    foreach($_left_info->find('p') as $_tag_p) {
        if ($_tag_p != null) {
            switch($_tag_p_index) {
                case 0:
                    $author = $_tag_p->find('a',0);
                    if ($author != null) {
                        $_book[':author'] = $author->plaintext;
                        $_book[':author'] = str_replace('&nbsp;','', $_book[':author']);
                    }
                    break;
                case 1:
                    $ctg = '';
                    $theloai = $_tag_p->find('.ds-theloai', 0);
                    if ($theloai != null) {
                        foreach($theloai->find('span') as $_span) {
                            if ($_span != null) {
                                if(strlen($ctg) > 1) {
                                    $ctg = $ctg . ' , ';
                                }
                                $ctg = $ctg . $_span->plaintext;
                            }
                        }
                    }
                    $_book[':category'] = $ctg;
                    break;
                case 2:
                    $type_book = $_tag_p->find('span', 1);
                    if ($type_book != null) {
                        $_book[':type_book'] = $type_book->plaintext;
                    } else {
                        $_book[':type_book'] = null;
                    }

                    break;
                case 3:
                    $view = $_tag_p->find('span', 1);
                    if ($view != null) {
                        $_book[':view'] = $view->plaintext;
                        $_book[':view'] = str_replace('&nbsp;','', $_book[':view']);
                    }
                    break;
            }
        }
        $_tag_p_index++;
    }

    //get right page content;
    $_right_info = $html->find('.rofx', 0);
    if($_right_info != null) {
        // Get rating
        $foo2 = $_right_info->find('.foo2', 0);
        if ($foo2 != null) {
            foreach ($foo2->find('span') as $span) {
                if ($span != null) {
                    if ($span->itemprop == 'average') {
                        $_book[':point_rating'] = $span->plaintext;
                    } elseif ($span->itemprop == 'votes') {
                        $_book[':total_rating'] = $span->plaintext;
                    }
                }
            }

            if (isset($_book[':point_rating']) && isset($_book[':total_rating'])) {
                $_book[':point_rating'] = $_book[':total_rating'] * $_book[':point_rating'];
            }

        }

        // Get description
        $desc_story = $_right_info->find('#desc_story', 0);
        if ($desc_story != null) {
            $fblike = $desc_story->find('.fb-like', 0);
            if ($fblike != null) {
                $fblike->outertext='';
            }
            $plusone = $desc_story->find('#___plusone_0', 0);
            if ($plusone != null) {
                $plusone->outertext='';
            }

            $des = $desc_story->innertext;
            $_book[':description'] = str_replace('Giới thiệu Truyện', '', $des);
        }
    }

    return $_book;
}

/**
 * Get total pager chapter of book
 * It will call on main page;
 * @param $html
 * @return int total pager;
 */
function get_total_pager_chapter($html) {
    $pagination = $html->find('.pagination', 0);
    if ($pagination != null) {
        $a = $pagination->find('a');
        if ($a != null) {
            return $a[sizeof($a)-2]->plaintext;
        }
    }
    return 0;
}

/**
 * get and insert list chapter to db;
 * It will call on main page;
 *
 * @param $conn
 * @param $book_id
 * @param $html
 */
function get_list_chapter_on_pager($conn, $book_id, $html) {
    if ($html != null) {
        $dschuong = $html->find('#dschuong', 0);
        if ($dschuong != null) {
            // get chapter content
            foreach ($dschuong->find('div') as $div) {
                $clss = $div->class;
                if ($clss == null || strlen($clss) < 2) {

                    $chapter = array(':id_book' => $book_id, ':number'=>null, ':name' => null, ':source_link'=> null);

                    $span = $div->find('span', 0);
                    if ($span != null) {
                        $chapter[':number'] = $span->plaintext;
                        $chapter[':number'] = str_replace('.', '', $chapter[':number']);
                    }
                    $a = $div->find('a', 0);
                    if ($a != null) {
                        $chapter[':source_link'] = $a->href;
                        $chapter[':name'] = $a->plaintext;
                        $chapter[':name'] = str_replace('&nbsp;', '', $chapter[':name']);
                    }

                    insert_chapter($conn, $chapter);
                }
            }
        }
    }
}
