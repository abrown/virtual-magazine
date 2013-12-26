<?php

require 'required-include.php';

/**
 * Allow higher time limit
 */
// set_time_limit(60);
//pr(ini_get('upload_max_filesize'));
//pr(ini_get('post_max_size'));
//pr(ini_get('memory_limit'));

/**
 * Set some variables: POST_MAX_SIZE, FILE_NAME
 */
$unit = strtoupper(substr(ini_get('post_max_size'), -1));
$multiplier = ($unit == 'M' ? 1048576 : ($unit == 'K' ? 1024 : ($unit == 'G' ? 1073741824 : 1)));
$POST_MAX_SIZE = $multiplier * (int) ini_get('post_max_size');
$FILE_NAME = md5(uniqid('file'));
$FILE_PATH = getcwd() . '/upload/' . $FILE_NAME;

/**
 * This serves as the authentication; a session variable ('USER_CAN_UPLOAD') is
 * set in magazine-create.php and the session ID is passed through SWFUpload as 
 * a POST parameter. We recover the session ID and test that the user had an
 * authorized session.
 */
// set session location (for some reason, /tmp was failing)
//session_save_path(get_base_dir() . DS . '..' . DS . 'session');
//ini_set('session.gc_probability', 1);
// set session ID
if (isset($_REQUEST['php_session_id'])) {
    session_id($_REQUEST['php_session_id']);
}
// check session variable
if (!WebSession::get('USER_CAN_UPLOAD')) {
    header("HTTP/1.1 403 Forbidden");
    echo 'Error: user is not logged in.';
    exit;
}
WebSession::clear(); // allows only one use; user must have visited magazine-create.php just prior to this call

/**
 *  From http://code.google.com/p/swfupload/
 * HTTP 4xx errors do not trigger error handling in SWFUpload; we will use an
 * 'Error: ' prefix to filter these.
 */
// check post_max_size
if (isset($_SERVER['CONTENT_LENGTH']) && (int) $_SERVER['CONTENT_LENGTH'] > $POST_MAX_SIZE && $POST_MAX_SIZE) {
    header("HTTP/1.1 400 Bad Request");
    echo "Error: POST exceeded the maximum allowed size.";
    exit();
}

// check for errors
$upload_name = 'Filedata';
if (!isset($_FILES[$upload_name])) {
    header("HTTP/1.1 400 Bad Request");
    echo "Error: no upload found in \$_FILES for " . $upload_name;
    exit();
}

// check extension
if (isset($_FILES[$upload_name]["error"]) && $_FILES[$upload_name]["error"] != 0) {
    header("HTTP/1.1 400 Bad Request");
    $errors = array(
        0 => "There is no error, the file uploaded with success",
        1 => "The uploaded file exceeds the upload_max_filesize directive in php.ini",
        2 => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form",
        3 => "The uploaded file was only partially uploaded",
        4 => "No file was uploaded",
        6 => "Missing a temporary folder"
    );
    echo 'Error: ' . $errors[$_FILES[$upload_name]["error"]];
    exit();
}

// check that file is uploaded to a temporary file
if (!isset($_FILES[$upload_name]["tmp_name"]) || !@is_uploaded_file($_FILES[$upload_name]["tmp_name"])) {
    header("HTTP/1.1 400 Bad Request");
    echo "Error: could not find uploaded file.";
    exit();
}

// check file extension
$path_info = pathinfo($_FILES[$upload_name]['name']);
$file_extension = $path_info["extension"];
if (strtolower($file_extension) != 'pdf') {
    header("HTTP/1.1 400 Bad Request");
    echo "Error: only PDF files are allowed.";
    exit();
}

// check the file size (Warning: the largest files supported by this code is 2GB)
$file_size = @filesize($_FILES[$upload_name]["tmp_name"]);
if (!$file_size) {
    header("HTTP/1.1 400 Bad Request");
    echo "Error: saved file under minimum file size.";
    exit();
}
if ($file_size > $POST_MAX_SIZE) {
    header("HTTP/1.1 400 Bad Request");
    echo "Error: saved file exceeded the maximum allowed size.";
    exit();
}

// move uploaded file
if (!@move_uploaded_file($_FILES[$upload_name]["tmp_name"], $FILE_PATH)) {
    header("HTTP/1.1 400 Bad Request");
    echo "Error: could not move the temporary file to: $FILE_PATH.";
    exit();
}

echo $FILE_NAME;