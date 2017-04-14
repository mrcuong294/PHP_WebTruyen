<?php set_time_limit(0);
/**
 * Created by PhpStorm.
 * User: pc
 * Date: 4/13/2017
 * Time: 2:23 PM
 */
require_once __DIR__ . '/../vendor/autoload.php';
require '../libs/simple_html_dom.php';
require '../config.php';
require '../functions.php';
require '../google_drive/DriveUtils.php';

// key secret appen file name;
define('SECRET_KEY', 'knjg');

try {
    $conn = getConnecDatabase();
    $query = $conn->prepare("SELECT id, id_book, number, source_link FROM wt_chapter 
              WHERE source_link IS NOT NULL AND source_link<>'' AND content_trans IS NULL");
    $query->execute();

    if($query->rowCount() > 0) {
        $driveUtil = new DriveUtils();
        $bookId = -1;
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {

            $file_name = $row['id_book'] . '_' . $row['number'] . '_' . $row['id'] .'_'. SECRET_KEY;
            $content = get_content_chap($row['source_link']);

            if ($content != null) {
                if ($bookId != $row['id_book']) {
                    $bookId = $row['id_book'];
                    // Create new folder chapter for book;
                    $driveUtil->createFolder('book_'.$bookId);
                }

                // Updaload file to folder chapter on google drive;
                $drive_file_id = $driveUtil->uploadChapterContent($file_name, $content);
                if ($drive_file_id != null) {
                    // Update info to table chapter on DB;
                    update_chapter_info($conn, $row['id'], $drive_file_id);
                } else {
                    echo '<br/>FAILED upload file ' . $file_name;
                }
            } else {
                echo '<br/>FAILED content file ' . $file_name . ' is null';
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

function update_chapter_info($conn, $id, $drive_file_id) {
    //$conn = getConnecDatabase();
    $query = $conn->prepare("UPDATE wt_chapter SET content_trans=:content_trans WHERE id=:id");
    $done = $query->execute(array(':id' => $id, ':content_trans' => $drive_file_id));
    if ($done) {
        echo '<br/>DONE update chapter id: ' . $id;
    } else {
        echo '<br/>FAILED update chapter id: ' . $id;
    }
}