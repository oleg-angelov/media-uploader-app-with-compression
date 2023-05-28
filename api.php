<?php

include('config.php');
include('classes/file.php');

if (empty($_GET)) {
    die();
}

$request = $_GET['request'];
$response = '';

if ($request == 'listFiles') {
    $fileLister = new FileLister(UPLOADS_FOLDER);
    $response = $fileLister->getFiles();
}

if ($request == 'uploadFile') {
    $fileUploader = new FileUploader(UPLOADS_FOLDER);
    $response = $fileUploader->uploadFile($_FILES['media']);
}

header('Content-Type: application/json');
echo json_encode($response);
