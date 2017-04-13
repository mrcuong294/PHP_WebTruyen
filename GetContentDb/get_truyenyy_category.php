<?php
/**
 * Created by PhpStorm.
 * User: Mr Cuong
 * Date: 4/10/2017
 * Time: 8:26 PM
 */

require '../config.php';

$_ctg_str = 'Kiếm Hiệp,Tiên Hiệp,Huyển Ảo,Sắc Hiệp,Đô Thị,Võng Du,Xuyên Không,Dị Giới,Dị Năng,Huyền Huyễn,Khoa Huyễn,Tu Chân,Lịch Sử Quân Sự,Viễn Tưởng,Trinh Thám,Ngôn Tình,Quan Trường,Truyện Teen,Thám Hiểm,Kỳ Bí,Ma Pháp,Quỷ Tu,Cổ Đại';
$_ctgs = explode(',', $_ctg_str);

try {
    $conn = getConnecDatabase();
    foreach($_ctgs as $ctg) {
        $query = $conn->prepare("INSERT INTO wt_category (name) VALUES (:name)");
        $query->execute(array(':name' => $ctg));
        echo '<br/>SUCCESS : category name : ' . $ctg;
    }
} catch (PDOException $e) {
    //file_put_contents('PDOErrors.txt', $e->getMessage(), FILE_APPEND);
    echo '<br/>Error DB: ' . $e->getMessage();
}