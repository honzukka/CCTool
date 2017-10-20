<?php
require_once "helper_functions.php";

// loads the file, deletes it and processes its content
function process_amex($target_file_path, $file_type)
{
	$file_handler = fopen($target_file_path, "r");
	
	if ($file_handler === FALSE)
	{
		print error_response_json("There was an error loading the file. Please contact the server administrator about this.");
		exit;
	}
	
	$transactions_json_array = array();
	$accounts_json_array = array();
	
	$actual_file_type = "";
	
	// read the file line by line
	while (!feof($file_handler))
	{
		$line = fgets($file_handler);
		
		// if the record is type 0 - header
		if ($line[0] == "0")
		{
			// read the file type
			$actual_file_type = "amex" . trim(substr($line, 27, 7), " ");
			
			// check the file type
			if ($file_type != $actual_file_type)
			{
				print error_response_json("Wrong file type.");
				exit_script($file_handler, $target_file_path);
			}
		}
		
		switch ($file_type)
		{
			case "amexGL1025":
				// if the record is type 1
				if ($line[0] == "1")
				{
					$transaction_json = get_transaction1025($line);
					array_push($transactions_json_array, $transaction_json);
				}
				break;
			case "amexGL1205":
				// if the record is type 1
				if ($line[0] == "1")
				{
					$account_json = get_account1205($line);
					array_push($accounts_json_array, $account_json);
				}
				break;
		}
	}
	
	fclose($file_handler);
	
	// delete the file as it's no longer needed
	if (!unlink($target_file_path))
	{
		print error_response_json("There was an error handling the file. Please contact the server administrator about this.");
		exit;
	}
	
	$result_json_array = array(
		"Error" => "",
		"Accounts" => $accounts_json_array,
		"AccountsMeta" => "NAME\t(ACCOUNT NUMBER)",
		"Transactions" => $transactions_json_array,
		"TransactionsMeta" => "TRANSACTION ID\t(ACCOUNT NUMBER)"
	);
	
	$result_json = json_encode($result_json_array);
	
	return $result_json;
}

function get_transaction1025($line)
{
	$panel_text = trim(substr($line, 631, 50), " ") . " (" . trim(substr($line, 207, 20)) . ")";
	
	$transaction_json = array(
		"Collapsible Panel Text" => $panel_text,
		"Employee ID" => trim(substr($line, 327, 15), " "),
		"First Name" => trim(substr($line, 257, 20), " "),
		"Last Name" => trim(substr($line, 227, 20), " "),
		"Transaction Type Code" => trim(substr($line, 898, 2), " "),
		"Charge Date" => trim(substr($line, 588, 10), " "),
		"Billing Date" => trim(substr($line, 616, 10), " "),
		"Local Charge Amount" => trim(substr($line, 811, 15), " "),
		"Local Currency Code" => trim(substr($line, 858, 3), " "),
		"Local Tax Amount" => trim(substr($line, 827, 15), " "),
		"Billed Amount" => trim(substr($line, 737, 15), " "),
		"Billed Currency Code" => trim(substr($line, 769, 3), " "),
		"Billed Tax Amount" => trim(substr($line, 753, 15), " "),
		"SE OU Business Name" => trim(substr($line, 1913, 40), " "),
		"SE Legal Name" => trim(substr($line, 1953, 40), " "),
		"SE Address Line 1" => trim(substr($line, 1997, 38), " "),
		"SE Address Line 2" => trim(substr($line, 2035, 38), " "),
		"SE Address Line 3" => trim(substr($line, 2073, 38), " "),
		"SE Address Line 4" => trim(substr($line, 2111, 38), " "),
		"SE City Name" => trim(substr($line, 2149, 38), " "),
		"SE State/Province Code" => trim(substr($line, 2190, 6), " "),
		"SE Postal Code" => trim(substr($line, 2196, 15), " "),
		"SE Country Name" => trim(substr($line, 2211, 40), " "),
		"SE Country Code" => trim(substr($line, 2251, 3), " ")
	);
	
	return $transaction_json;
}

function get_account1205($line)
{
	$panel_text = trim(substr($line, 158, 20), " ") . " " . trim(substr($line, 128, 30), " ") . " (" . trim(substr($line, 108, 20)) . ")";
	
	$account_json = array(
		"Collapsible Panel Text" => $panel_text,
		"Expire Date" => trim(substr($line, 732, 8), " "),
		"Employee ID" => trim(substr($line, 211, 15), " "),
		"Cardmember First Name" => trim(substr($line, 158, 20), " "),
		"Cardmember Middle Name" => trim(substr($line, 178, 20), " "),
		"Cardmember Last Name" => trim(substr($line, 128, 30), " "),
		"Address 1" => trim(substr($line, 429, 40), " "),
		"Address 2" => trim(substr($line, 469, 40), " "),
		"Address 3" => trim(substr($line, 509, 40), " "),
		"Address 4" => trim(substr($line, 549, 40), " "),
		"Address 5" => trim(substr($line, 589, 40), " "),
		"City" => trim(substr($line, 629, 35), " "),
		"State" => trim(substr($line, 664, 6), " "),
		"Postal Code" => trim(substr($line, 670, 15), " "),
		"Country Code" => trim(substr($line, 685, 3), " ")
	);
	
	return $account_json;
}

?>