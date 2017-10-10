<?php
// returns the error json response
function error_response_json($error_string)
{
	$response_array = array("Error" => $error_string);
	$response_json = json_encode($response_array);
	return $response_json;
}

function reduce_spaces($old_string)
{
	$old_string_length = strlen($old_string);
	if ($old_string_length == 0)
	{
		return $old_string;
	}
	
	$new_string = "";
	$current_character = $old_string[0];
	
	for ($i = 1; $i < $old_string_length; $i++)
	{
		if ($current_character == " " && $i == $old_string_length - 1)
		{
			break;
		}
		else if ($current_character == " " && $old_string[$i] == " ")
		{
			continue;
		}
		
		$current_character = $old_string[$i];
		$new_string = $new_string . $current_character;
	}
	
	return $new_string;
}
?>