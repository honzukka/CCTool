<?php
// returns the error json response
function error_response_json($error_string)
{
	$response_array = array("Error" => $error_string);
	$response_json = json_encode($response_array);
	return $response_json;
}

function exit_script($file_handler, $target_file_path)
{
	fclose($file_handler);
	
	// delete the file
	if (!unlink($target_file_path))
	{
		print error_response_json("There was an error handling the file. Please contact the server administrator about this.");
	}
	
	exit;
}
?>