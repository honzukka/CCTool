<?php
// returns the error json response
function error_response_json($error_string)
{
	$response_array = array("Error" => $error_string);
	$response_json = json_encode($response_array);
	return $response_json;
}
?>