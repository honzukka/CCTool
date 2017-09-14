<?php
$target_dir = "uploads/";
$target_file = $target_dir . basename( $_FILES["fileToUpload"]["name"]);
$uploadOk = 1;

if (!move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) 
{
	echo "Sorry, there was an error uploading your file.";
}

$file = fopen($target_file, "r") or die("Unable to open file!");
print fread($file,filesize($target_file));
fclose($file);
?>