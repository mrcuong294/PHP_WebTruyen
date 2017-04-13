<?php set_time_limit(0);
/**
 * Created by PhpStorm.
 * User: Mr Cuong
 * Date: 4/12/2017
 * Time: 12:04 AM
 */
require '../libs/simple_html_dom.php';
require '../config.php';
require '../functions.php';

define('BASE_URL', 'http://sstruyen.com');

$url = 'http://sstruyen.com/doc-truyen/ngon-tinh/choc-gian-bao-boi-ong-xa-cung-chieu-nhe-mot-chut/chuong-904-nho-phai-giu-bi-mat/1129954.html';

try {
    $startPos = 79265;
    $totalSuccess = 0;
    $conn = getConnecDatabase();
    $query = $conn->prepare("SELECT id, source_link FROM wt_chapter WHERE source_link IS NOT NULL AND source_link<>'' AND content_trans IS NULL
                                                                        LIMIT " . $startPos . ", 1500000");
    $query->execute();

    if($query->rowCount() > 0) {
        echo '<br/>START AT POS : ' . $startPos;
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $content = get_chapter_content(BASE_URL . $row['source_link']);
            if($content != null) {
                $success = update_chapter_content($conn, $row['id'], $content);
                if($success) {
                    $totalSuccess++;
                } else {
                    echo '<br/><span style="color:#ee0000">FAILURE : update content chapter id : ' . $row['id'] . '</span> ';
                }
            }
        }
        echo '<br/>END - Success total : ' . $totalSuccess;
    }
} catch (PDOException $e) {
    //file_put_contents('PDOErrors.txt', $e->getMessage(), FILE_APPEND);
    echo '<br/>Error DB: ' . $e->getMessage();
}

/**
 * Get content chapter from source link;
 * @param $url
 * @return mixed|null
 */
function get_chapter_content($url) {
    $data = null;
    $html = curl($url);
    if(strlen($html) > 0) {
        //echo '<br/>if(strlen($html) > 0)';
        $scripts = explode('<script>var nChaptId = ', $html);
        if(isset($scripts[1]) && strlen($scripts[1]) > 0) {
            //echo '<br/>if(isset($scripts[1]) && strlen($scripts[1]) > 0)';
            $ss = explode('";var szChapterTime = szChapterTime', $scripts[1]);
            if($ss[0] != null && strlen($ss[1]) > 0) {
                //echo '<br/>if($ss[0] != null && strlen($ss[1]) > 0)';
                $sss = explode(';var szChapterTime = "', $ss[0]);
                if ($sss[0] != null && strlen($sss[1]) > 0 && isset($sss[1])) {
                    //echo '<br/>if ($sss[0] != null && strlen($sss[1]) > 0 && isset($sss[1]))';
                    $time = str_replace('-', '', $sss[1]);
                    $time = str_replace(' ', '', $time);
                    $time = str_replace(':', '', $time);
                    $urlAjax = 'http://sstruyen.com/doc-truyen/index.php?ajax=ct&id=' . $sss[0] . '&t=' . $time;
                    //echo '<br />' . $urlAjax;
                    $data = curl($urlAjax);
                }
            }
        }
    }
    return $data;
}

function update_chapter_content($conn, $id, $content) {
    //$conn = getConnecDatabase();
    $query = $conn->prepare("UPDATE wt_chapter SET content_trans = :content_trans WHERE id = :id");
    $sucs = $query->execute(array(
        ':id' => $id,
        ':content_trans' => $content
    ));
    return $sucs;
}