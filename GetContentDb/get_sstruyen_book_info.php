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

$base_url = 'http://sstruyen.com';


try {
    $conn = getConnecDatabase();
    $query_get = $conn->prepare("SELECT id, source_link FROM wt_book LIMIT 12758, 10000");
    $query_get->execute();

    if($query_get->rowCount() > 0) {
        while ($row = $query_get->fetch(PDO::FETCH_ASSOC)) {
            $_book = get_book_info($base_url . $row['source_link']);
            $_book[':id'] = $row['id'];
            $_book[':type_book'] = 'Truyện dịch';
            update_book_info($conn, $_book);
        }
    }
} catch (PDOException $e) {
    //file_put_contents('PDOErrors.txt', $e->getMessage(), FILE_APPEND);
    echo '<br/>Error DB: ' . $e->getMessage();
}

/**
 * Lấy thông tin truyện
 * Array book : poster, author, category, total_chapter, view, total_rating, point_rating, description
 *
 * @param $sourceLink / Link nguồn
 * @return null / Array book,
 */
function get_book_info($sourceLink) {
    $base_url = 'http://sstruyen.com';
    $html = file_get_html($sourceLink);
    /*
    $page_data = curl($sourceLink);
    $html = new simple_html_dom();
    $html->load($page_data);*/
    $_book = null;
    $_truyen_info = $html->find('.truyeninfo', 0);

    $_book[':poster'] = $base_url . $_truyen_info->find('img', 0)->src;

    $_tag_index = 0;
    foreach($_truyen_info->find('.cp2') as $info) {
        switch($_tag_index) {
            case 0:
                $_book[':author'] = $info->find('span', 0)->plaintext;
                break;
            case 1:
                $ctg = '';
                foreach($info->find('a') as $a) {
                    if(strlen($ctg) > 1) {
                        $ctg = $ctg . ' , ';
                    }
                    $ctg = $ctg . $a->plaintext;
                }
                $_book[':category'] = $ctg;
                break;
            case 2:
                if($info->find('span')) {
                    $_tag_index--;
                } else{
                    $_book[':total_chapter'] = $info->plaintext;
                }
                break;
            case 4:
                $_book[':view'] = $info->plaintext;
                break;
        }
        $_tag_index++;
    }

    $rank = $_truyen_info->find('.rank', 0);
    foreach($rank->find('span') as $rk) {

        if ($rk->itemprop == 'reviewCount') {
            $text = $rk->plaintext;
            if(strlen($text)) {
                $texts = explode(' ', $text);
                $_book[':total_rating'] = $texts[0];
            }
        } elseif($rk->itemprop == 'ratingValue') {
            $rating = (int)$rk->plaintext;
            if(isset($_book[':total_rating'])) {
                $total =  (int)$_book[':total_rating'];
                $rating = $total * $rating;
            }
            $_book[':point_rating'] = $rating;
        }
    }

    $des = $html->find('.story_description', 0);
    $des->find('div', 0)->outertext='';
    $desStr = $des->innertext;
    $desStr = str_replace('Hãy LIKE để ủng hộ sstruyen các bạn nhé ...','',$desStr);
    $_book[':description'] = $desStr;

    return $_book;
}

/**
 * Update book infomation;
 * @param $conn
 * @param $_book
 */
function update_book_info($conn, $_book) {
    $query = $conn->prepare("UPDATE wt_book SET poster=:poster,
                                                author=:author,
                                                category=:category,
                                                total_chapter=:total_chapter,
                                                view=:view,
                                                total_rating = :total_rating,
                                                point_rating = :point_rating,
                                                description = :description,
                                                type_book = :type_book
                                                 WHERE id = :id");
    if($query->execute($_book)) {
        echo '<br/>SUCCESS : add book ID : ' . $_book[':id'];
    } else {
        echo '<br/>FAILURE : add book ID : ' . $_book[':id'];
    }
}
