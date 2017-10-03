<?php
// rquire code necessary to process different card formats
require_once "process_mastercard.php";

// this supresses warning messages (unlink prints warning messages so an "invalid json" is sent to the html page)
// error messages are still on
error_reporting(E_ALL ^ E_WARNING);

// UPLOAD
$target_dir = "uploads/";
$target_file_path = $target_dir . basename( $_FILES["fileUpload"]["name"]);

if (!move_uploaded_file($_FILES["fileUpload"]["tmp_name"], $target_file_path)) 
{
	$response_array = array("Error" => "There was an error uploading your file.");
	$response_json = json_encode($response_array);
	print $response_json;
	exit;
}

// LOAD THE FILE, DELETE IT AND PROCESS THE CONTENT
$response_json = process_mastercard($target_file_path);

// OUTPUT
print $response_json;

?>