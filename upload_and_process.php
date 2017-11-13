<?php
// require code necessary to process different card formats
require_once "process_mastercard.php";
require_once "process_visa.php";
require_once "process_amex.php";
require_once "helper_functions.php";

// this supresses warning messages (unlink prints warning messages so an "invalid json" is sent to the html page)
// error messages are still on
error_reporting(E_ALL ^ E_WARNING);

// UPLOAD
$target_dir = "uploads/";
$target_file_path = $target_dir . basename( $_FILES["fileUpload"]["name"]);

if (!move_uploaded_file($_FILES["fileUpload"]["tmp_name"], $target_file_path)) 
{
	print error_response_json("There was an error uploading the file.");
	exit;
}

$response_json = array();

// LOAD THE FILE, DELETE IT AND PROCESS THE CONTENT
if ($_POST["seltype"] == "mc")
{
	$response_json = process_mastercard($target_file_path);
}
else if ($_POST["seltype"] == "v40" || $_POST["seltype"] == "v44")
{
	$response_json = process_visa($target_file_path);
}
else if ($_POST["seltype"] == "amexGL1025" || $_POST["seltype"] == "amexGL1205" || 
		$_POST["seltype"] == "amexTMKD" || $_POST["seltype"] == "amexGL1080" ||
		$_POST["seltype"] == "amexKR1025" || $_POST["seltype"] == "amexKR1205")
{
	$response_json = process_amex($target_file_path, $_POST["seltype"]);
}

// OUTPUT
print $response_json;

?>