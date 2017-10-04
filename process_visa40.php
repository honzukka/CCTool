<?php
// loads the file, deletes it and processes its content
function process_visa40($target_file_path)
{
	$file_handler = fopen($target_file_path, "r");
	
	if ($file_handler === FALSE)
	{
		$response_array = array("Error" => "There was an error loading the file. Please contact the server administrator about this.");
		$response_json = json_encode($response_array);
		print $response_json;
		exit;
	}
	
	$cardholders_json_array = array();
	$accounts_json_array = array();
	$transactions_json_array = array();
	
	// read the file line by line
	while (!feof($file_handler))
	{
		$line = fgets($file_handler);
		$line_array = preg_split("/[\t\ ]+/", $line);
		
		// enter set
		if ($line_array[0] == "6")
		{
			// TODO: check if the file is indeed VISA 4.0
			process_set($file_handler, $cardholders_json_array, $accounts_json_array, $transactions_json_array);
		}
	}
	
	fclose($file_handler);
	
	$result_json_array = array(
		"Error" => "",
		"Accounts" => $accounts_json_array,
		"Cardholders" => $cardholders_json_array,
		"Transactions" => $transactions_json_array
	);
	
	$result_json = json_encode($result_json_array);
	
	return $result_json;
}

// appends records in a set to corresponding arrays
function process_set($file_handler, &$cardholders_json_array, &$accounts_json_array, &$transactions_json_array)
{
	while (!feof($file_handler))
	{
		$line = fgets($file_handler);
		$line_array = preg_split("/[\t\ ]+/", $line);
		
		// exit set
		if ($line_array[0] == "7")
		{
			return;
		}
		
		// enter block
		if ($line_array[0] == "8")
		{
			switch ($line_array[4])
			{
				case "04": 
					$cardholders_json_array = array_merge($cardholders_json_array, process_cardholders($file_handler)); 
					break;
				case "03": 
					$accounts_json_array = array_merge($accounts_json_array, process_accounts($file_handler)); 
					break;
				case "05": 
					$transactions_json_array = array_merge($transactions_json_array, process_transactions($file_handler)); 
					break;
				default: 
					break;
			}
		}
	}
	
	// TODO: wrong file format here!
}

// returns an array of cardholders
function process_cardholders($file_handler)
{
	$cardholders_json_array = array();
	
	while (!feof($file_handler))
	{
		$line = fgets($file_handler);
		$line_array = preg_split("/[\t\ ]+/", $line);
		
		// exit block
		if ($line_array[0] == "9")
		{
			return $cardholders_json_array;
		}
		
		$panel_text = $line_array[4] . " " . $line_array[5] 
		
		$cardholder_json = array(
			"Collapsible Panel Text" => $panel_text,
			"Employee ID" => $line_array[22],
			"Address Line 1" => $line_array[6],
			"Address Line 2" => $line_array[7],
			"City" => $line_array[8],
			"State/Province Code" => $line_array[9],
			"Postal Code" => $line_array[11],
			"Cardholder Identification" => $line_array[2]
		);
		
		array_push($cardholders_json_array, $cardholder_json);
	}
	
	// TODO: wrong file format here!
}

// returns an array of accounts
function process_accounts($file_handler)
{
	$accounts_json_array = array();
	
	while (!feof($file_handler))
	{
		$line = fgets($file_handler);
		$line_array = preg_split("/[\t\ ]+/", $line);
		
		// exit block
		if ($line_array[0] == "9")
		{
			return $accounts_json_array;
		}
	}
	
	// TODO: wrong file format here!
}

// returns an array of transactions
function process_transactions($file_handler)
{
	$transactions_json_array = array();
	
	while (!feof($file_handler))
	{
		$line = fgets($file_handler);
		$line_array = preg_split("/[\t\ ]+/", $line);
		
		// exit block
		if ($line_array[0] == "9")
		{
			return $transactions_json_array;
		}
	}
	
	// TODO: wrong file format here!
}

?>