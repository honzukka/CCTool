<?php
require_once "helper_functions.php";

// loads the file, deletes it and processes its content
function process_visa($target_file_path)
{
	$file_handler = fopen($target_file_path, "r");
	
	if ($file_handler === FALSE)
	{
		print error_response_json("There was an error reading the file. Please contact the server administrator about this.");
		exit;
	}
	
	$cardholders_json_array = array();
	$accounts_json_array = array();
	$transactions_json_array = array();
	
	$visa_records_found = FALSE;
	
	// read the file line by line
	while (!feof($file_handler))
	{
		$line_array = get_split_line($file_handler);
		
		// enter set
		if ($line_array[0] == "6")
		{
			$format_type = trim($line_array[7], " ");
			
			// check if the file is indeed VISA 4.0 or VISA 4.4
			if ($format_type != "4.0" && $format_type != "4.4")
			{
				print error_response_json("Incorrect file format: Not VISA VCF 4.0 or VISA VCF 4.4");
				exit_script($file_handler, $target_file_path);
			}
			
			process_set($file_handler, $cardholders_json_array, $accounts_json_array, $transactions_json_array);
			$visa_records_found = TRUE;
		}
	}
	
	if ($visa_records_found == FALSE)
	{
		print error_response_json("File either empty or in an incorrect format.");
		exit_script($file_handler, $target_file_path);
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
		"AccountsMeta" => "ACCOUNT NUMBER\t(CARDHOLDER IDENTIFICATION)",
		"Cardholders" => $cardholders_json_array,
		"CardholdersMeta" => "NAME\t(CARDHOLDER IDENTIFICATION)",
		"Transactions" => $transactions_json_array,
		"TransactionsMeta" => "TRANSACTION REFERENCE NUMBER\t(ACCOUNT NUMBER)",
	);
	
	$result_json = json_encode($result_json_array);
	
	return $result_json;
}

// appends records in a set to corresponding arrays
function process_set($file_handler, &$cardholders_json_array, &$accounts_json_array, &$transactions_json_array)
{
	while (!feof($file_handler))
	{
		$line_array = get_split_line($file_handler);
		
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
	
	print error_response_json("Incorrect file format: Not VISA VCF 4.X");
	exit;
}

// returns an array of cardholders
function process_cardholders($file_handler)
{
	$cardholders_json_array = array();
	
	while (!feof($file_handler))
	{
		$line_array = get_split_line($file_handler);
		trim_line_array($line_array);
		
		// exit block
		if ($line_array[0] == "9")
		{
			return $cardholders_json_array;
		}
		
		$panel_text = $line_array[4] . " " . $line_array[5] . " (" . $line_array[2] . ")";
		
		$cardholder_json = array(
			"Collapsible Panel Text" => $panel_text,
			"Employee ID" => $line_array[22],
			"Address Line 1" => $line_array[6],
			"Address Line 2" => $line_array[7],
			"City" => $line_array[8],
			"State/Province Code" => $line_array[9],
			"Postal Code" => $line_array[11]
			//"Cardholder Identification" => $line_array[2]
		);
		
		array_push($cardholders_json_array, $cardholder_json);
	}
	
	print error_response_json("Incorrect file format: Not VISA VCF 4.X");
	exit_script($file_handler, $target_file_path);
}

// returns an array of accounts
function process_accounts($file_handler)
{
	$accounts_json_array = array();
	
	while (!feof($file_handler))
	{
		$line_array = get_split_line($file_handler);
		trim_line_array($line_array);
		
		// exit block
		if ($line_array[0] == "9")
		{
			return $accounts_json_array;
		}
		
		$obscured_account_number = "*" . substr($line_array[2], -4);
		$panel_text = $obscured_account_number . " (" . $line_array[1] . ")";
		
		$account_json = array(
			"Collapsible Panel Text" => $panel_text,
			"Effective Date" => $line_array[4],
			"Card Expire Date" => $line_array[7]
			//"Cardholder Identification" => $line_array[1]
		);
		
		array_push($accounts_json_array, $account_json);
	}
	
	print error_response_json("Incorrect file format: Not VISA VCF 4.X");
	exit_script($file_handler, $target_file_path);
}

// returns an array of transactions
function process_transactions($file_handler)
{
	$transactions_json_array = array();
	
	while (!feof($file_handler))
	{
		$line_array = get_split_line($file_handler);
		trim_line_array($line_array);
		
		// exit block
		if ($line_array[0] == "9")
		{
			return $transactions_json_array;
		}
		
		$panel_text = $line_array[3] . " (" . $line_array[1] . ")";
		
		$transaction_json = array(
			"Collapsible Panel Text" => $panel_text,
			"Transaction Type Code" => $line_array[17],
			"Posting Date" => $line_array[2],
			"Source Amount" => $line_array[13],
			"Source Currency Code" => $line_array[15],
			"Billing Amount" => $line_array[14],
			"Tax Amount" => $line_array[20],
			"Customer VAT Number" => $line_array[27],
			"Memo Post Flag" => $line_array[51],
			"Card Acceptor" => $line_array[7],
			"Supplier Name" => $line_array[8],
			"Supplier City" => $line_array[9],
			"Supplier State/Province" => $line_array[10],
			"Supplier Postal Code" => $line_array[12],
			"Supplier ISO Country Code" => $line_array[11],
			"Supplier VAT Number" => $line_array[25]
			//"Account Number" => $line_array[1]
		);
		
		array_push($transactions_json_array, $transaction_json);
	}
	
	print error_response_json("Incorrect file format: Not VISA VCF 4.X");
	exit_script($file_handler, $target_file_path);
}

function get_split_line($file_handler)
{
	$line = fgets($file_handler);
	return preg_split("/\t/", $line, NULL);
}

// trims spaces in all parts of the line array and return the modified array
// argument passed bvy reference!!!
function trim_line_array(&$line_array)
{
	$array_count = count($line_array);
	
	for ($i = 0; $i < $array_count; $i++)
	{
		$line_array[$i] = trim($line_array[$i], " ");
	}
	
	return $line_array;
}

?>