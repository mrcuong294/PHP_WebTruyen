<?php
/**
 * Created by PhpStorm.
 * User: Mr Cuong
 * Date: 4/13/2017
 * Time: 10:54 PM
 */
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../google_drive/DriveUtils.php';

$content = 'When uploading media, be sure to follow these best practices related to error handling:';
$dive = new DriveUtils();
$id = $dive->uploadChapterContent('abcd', $content);
if ($id != null) {
    echo '<br/>Upload success ID : ' . $id;
} else {
    echo '<br/>Upload Failed!';
}