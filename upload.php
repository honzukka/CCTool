<?php
// UPLOAD
$target_dir = "uploads/";
$target_file = $target_dir . basename( $_FILES["fileToUpload"]["name"]);
$uploadOk = 1;

if (!move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) 
{
	echo "Sorry, there was an error uploading your file.";
	exit;
}

/* SIMPLE TEST OUTPUT
$file = fopen($target_file, "r") or die("Unable to open file!");
print fread($file,filesize($target_file));
fclose($file);
*/

// OPEN + RENDER
libxml_use_internal_errors(true);
$myfile = file_get_contents($target_file, true, NULL);

// deserialize
$xml = simplexml_load_string($myfile);

// if the deserialization failed, print error messages and exit
if ($xml === false)
{
	$error_message = "Failed loading XML:" . PHP_EOL;
	foreach(libxml_get_errors() as $error)
	{
		$error_message = $error_message . $error->message . PHP_EOL;
	}
	$responseArray = array("error" => $error_message);
	$responseJson = json_encode($responseArray);
	print $responseJson;
	exit;
}
?>