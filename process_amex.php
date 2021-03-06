<?php
require_once "helper_functions.php";

// loads the file, deletes it and processes its content
function process_amex($target_file_path, $file_type)
{
	$file_handler = fopen($target_file_path, "r");
	
	if ($file_handler === FALSE)
	{
		print error_response_json("There was an error reading the file. Please contact the server administrator about this.");
		exit;
	}
	
	$transactions_json_array = array();
	$accounts_json_array = array();
	
	$transactions_meta_string = "TRANSACTION ID\t(ACCOUNT NUMBER)";
	$accounts_meta_string = "NAME\t(ACCOUNT NUMBER)";
	
	$actual_file_type = "";
	
	switch ($file_type)
	{
		case "amexGL1025":
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
				
				// extract the transaction information from the relevant records (type 1)
				if ($line[0] == "1")
				{
					$transaction_json = get_transaction1025($line, $transactions_meta_string);
					array_push($transactions_json_array, $transaction_json);
				}
			}
			break;
		case "amexGL1205":
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
				
				// extract the transaction information from the relevant records (type 1)
				if ($line[0] == "1")
				{
					$account_json = get_account1205($line, $accounts_meta_string);
					array_push($accounts_json_array, $account_json);
				}
			}
			break;
		case "amexTMKD":
			// read the file line by line
			while (!feof($file_handler))
			{
				$line = fgets($file_handler);
				
				// extract the transaction information from the relevant records (type F)
				if ($line[15] == "F")
				{
					$transaction_json = get_transactionTKMD($line, $transactions_meta_string);
					array_push($transactions_json_array, $transaction_json);
				}
			}
			break;
		case "amexGL1080":
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
				
				// extract the transaction information from the relevant records (type 1)
				if ($line[0] == "1")
				{
					// the file handler needs to be passed because the function reads "nested" records
					$transaction_json = get_transaction1080($line, $file_handler, $transactions_meta_string);
					array_push($transactions_json_array, $transaction_json);
				}
			}
			break;
		case "amexKR1025":
			// read the file line by line
			while (!feof($file_handler))
			{
				$line = fgets($file_handler);
				
				// if the record is type 0 - header
				if ($line[0] == "0")
				{	
					// read the file type
					$actual_file_type = "amex" . trim(substr($line, 22, 2), " ") . trim(substr($line, 25, 4), " ");
					
					// check the file type
					if ($file_type != $actual_file_type)
					{
						print error_response_json("Wrong file type.");
						exit_script($file_handler, $target_file_path);
					}
				}
				
				// if the record is either an account without transactions or a transaction
				if ($line[0] == "1" || $line[0] == "2")
				{
					$account_json = get_accountKR1025($line, $file_handler, $accounts_meta_string);
					array_push($accounts_json_array, $account_json);
				}
			}
			break;
		case "amexKR1205":
			// read the file line by line
			while (!feof($file_handler))
			{
				$line = fgets($file_handler);
				
				// if the record contains accounts
				if ($line[0] == "2")
				{
					$account_json = get_accountKR1205($line, $accounts_meta_string);
					array_push($accounts_json_array, $account_json);
				}
			}
			break;
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
		"AccountsMeta" => $accounts_meta_string,
		"Transactions" => $transactions_json_array,
		"TransactionsMeta" => $transactions_meta_string
	);
	
	$result_json = json_encode($result_json_array);
	
	return $result_json;
}

function get_transaction1025($line, &$meta_string)
{
	$panel_text = trim(substr($line, 631, 50), " ") . " (" . trim(substr($line, 207, 20)) . ")";
	$meta_string = "TRANSACTION ID\t(ACCOUNT NUMBER)";
	
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

function get_account1205($line, &$meta_string)
{
	$panel_text = trim(substr($line, 158, 20), " ") . " " . trim(substr($line, 128, 30), " ") . " (" . trim(substr($line, 108, 20)) . ")";
	$meta_string = "CARDMEMBER NAME\t(CARDMEMBER NUMBER)";
	
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

function get_transactionTKMD($line, &$meta_string)
{
	$panel_text = trim(substr($line, 17, 15), " ") . " (" . trim(substr($line, 0, 15), " ") . ")";
	$meta_string = "RECORD TYPE REFERENCE\t(BUSINESS TRAVEL ACCOUNT NUMBER)";
	
	$transaction_json = array(
		"Collapsible Panel Text" => $panel_text,
		"Place Processing the Transaction" => trim(substr($line, 48, 45), " "),
		"Invoice Number" => trim(substr($line, 103, 9), " "),
		"Statement Date of Transaction" => trim(substr($line, 113, 8), " "),
		"Traveller Name" => trim(substr($line, 129, 20), " "),
		"Destination of Travel/General Description" => trim(substr($line, 151, 24), " "),
		"Billing Amount" => trim(substr($line, 225, 15), " "),
		"VAT Amount where Captured/Received" => trim(substr($line, 303, 15), " "),
		"Charge Receipt Date" => trim(substr($line, 341, 8), " "),
		"REF. 1" => trim(substr($line, 175, 9), " "),
		"REF. 2" => trim(substr($line, 184, 24), " "),
		"REF. 3" => trim(substr($line, 349, 10), " "),
		"REF. 4" => trim(substr($line, 359, 10), " "),
		"REF. 5" => trim(substr($line, 470, 15), " "),
		"REF. 6" => trim(substr($line, 485, 5), " "),
		"REF. 7" => trim(substr($line, 490, 10), " ")
	);
	
	return $transaction_json;
}

function get_transaction1080($line, $file_handler, &$meta_string)
{
	$panel_text = trim(substr($line, 16, 50), " ") . " (" . trim(substr($line, 306, 20), " ") . ")";
	$meta_string = "CHARGE TRANSACTION ID\t(BILLING ACCOUNT NUMBER)";
	
	$transaction_json = array(
		"Collapsible Panel Text" => $panel_text,
		"Folio Record ID" => trim(substr($line, 1, 15), " "),
		"Charge Transaction ID (links to GL1025 or GL1026)" => trim(substr($line, 16, 50), " "),
		"Charge Transaction Number (links to KR1025)" => trim(substr($line, 66, 37), " "),
		"Requesting Control Account Number" => trim(substr($line, 143, 19), " "),
		"Requesting Control Account Name" => trim(substr($line, 162, 40), " "),
		"Billing Basic Control Account Number" => trim(substr($line, 202, 19), " "),
		"Billing Basic Control Account Name" => trim(substr($line, 221, 40), " "),
		"Billing Account Number" => trim(substr($line, 306, 20), " "),
		"Cardmember Embossed Name" => trim(substr($line, 326, 30), " "),
		"Cardmember Employee ID" => trim(substr($line, 356, 15), " "),
		"Charge Date" => trim(substr($line, 421, 10), " "),
		"Business Process Date" => trim(substr($line, 431, 8), " "),
		"Billed Amount" => trim(substr($line, 455, 15), " "),
		"Billed Currency ISO Code" => trim(substr($line, 486, 3), " "),
		"Local Charge Amount" => trim(substr($line, 490, 15), " "),
		"Local Currency ISO Code" => trim(substr($line, 521, 3), " "),
		"SE OU Business Name" => trim(substr($line, 553, 40), " "),
		"SE Legal Name" => trim(substr($line, 593, 40), " "),
		"SE Chain Name" => trim(substr($line, 663, 30), " "),
		"SE Address Line 1" => trim(substr($line, 693, 38), " "),
		"SE Address Line 2" => trim(substr($line, 731, 38), " "),
		"SE Address Line 3" => trim(substr($line, 769, 38), " "),
		"SE Address Line 4" => trim(substr($line, 807, 38), " "),
		"SE City Name" => trim(substr($line, 845, 38), " "),
		"SE State/Province Code" => trim(substr($line, 886, 6), " "),
		"SE Postal Code" => trim(substr($line, 892, 15), " "),
		"SE Country Name" => trim(substr($line, 907, 40), " "),
		"SE Country Code" => trim(substr($line, 947, 3), " "),
		"Hotel Type" => trim(substr($line, 1000, 40), " "),
		"Arrival Date" => trim(substr($line, 1120, 8), " "),
		"Departure Date" => trim(substr($line, 1138, 8), " "),
		"Booking Number" => trim(substr($line, 1159, 15), " ")
	);
	
	$line_items_json = array();
	
	// continue reading the file line by line
	while (!feof($file_handler))
	{
		$previous_position = ftell($file_handler);
		$line = fgets($file_handler);
		
		// if we have reached the following transaction
		if ($line[0] == "1")
		{
			// move the stream back and return
			fseek($file_handler, $previous_position);
			break;
		}
		
		// if we read a type-2 record
		if ($line[0] == "2")
		{
			$panel_text = trim(substr($line, 222, 40), " ");
			
			$line_item_json = array(
				"Collapsible Panel Text" => $panel_text,
				"Folio Record ID" => trim(substr($line, 1, 15), " "),
				"Charge Transaction ID (links to GL1025 or GL1026)" => trim(substr($line, 16, 50), " "),
				"Charge Transaction Number (links to KR1025)" => trim(substr($line, 66, 37), " "),
				"Item Code" => trim(substr($line, 111, 6), " "),
				"Item Code Description" => trim(substr($line, 117, 40), " "),
				"Item Amount" => trim(substr($line, 278, 15), " ")
			);
			
			array_push($line_items_json, $line_item_json);
		}
	}
	
	$transaction_json["Line Items"] = $line_items_json;
	$transaction_json["NestedMeta"] = "ITEM DESCRIPTION";
	
	return $transaction_json;
}

function get_accountKR1025($line, $file_handler, &$meta_string)
{
	// account without transactions - just extract it and return
	if ($line[0] == "2")
	{
		return get_accountKR1025_internal($line, $meta_string);
	}
	
	// keep extracting transactions until an account is found (then return)
	if ($line[0] == "1")
	{
		$transactions_json_array = array();

		// extract all the transactions
		do
		{
			$panel_text = trim(substr($line, 374, 15)) . " (" . trim(substr($line, 389, 8)) . ")";
			
			$transaction_json = array(
				"Collapsible Panel Text" => $panel_text,
				"Requesting Control Account Number" => trim(substr($line, 1, 19)),
				"Billing Basic Control Account Number" => trim(substr($line, 55, 19)),
				"Cardmember Embossed Name" => trim(substr($line, 132, 26)),
				"Employee ID" => trim(substr($line, 158, 10)),
				"Cost Center" => trim(substr($line, 168, 10)),
				"Corporate Identifier Number" => trim(substr($line, 214, 19)),
				"Supplier Reference Number" => trim(substr($line, 233, 11)),
				"Billed Amount" => trim(substr($line, 245, 15)),
				"Billed Tax Amount" => trim(substr($line, 260, 15)),
				"Billed Currency Code" => trim(substr($line, 275, 3)),
				"Local Charge Amount" => trim(substr($line, 279, 15)),
				"Local Tax Amount" => trim(substr($line, 294, 15)),
				"Local Currency Code" => trim(substr($line, 309, 3)),
				"Currency Exchange Rate" => trim(substr($line, 313, 15)),
				"Transaction Number" => trim(substr($line, 374, 15)),
				"Charge Date" => trim(substr($line, 389, 8)),
				"Business Process Date" => trim(substr($line, 397, 8)),
				"Bill Date" => trim(substr($line, 405, 8)),
				"SE Name 1" => trim(substr($line, 839, 40)),
				"SE Name 2" => trim(substr($line, 879, 40)),
				"SE City" => trim(substr($line, 999, 40)),
				"SE State" => trim(substr($line, 1039, 6)),
				"SE Zip Code" => trim(substr($line, 1045, 15)),
				"SE Country Name" => trim(substr($line, 1060, 35)),
				"SE Country Code" => trim(substr($line, 1095, 3))
			);
			
			array_push($transactions_json_array, $transaction_json);
			
			$line = fgets($file_handler);
		}
		while ($line[0] == "1");
			
		// now process the account and add the transactions to it
		$account_json = get_accountKR1025_internal($line, $meta_string);
		
		$account_json["Transactions"] = $transactions_json_array;
		$account_json["NestedMeta"] = "TRANSACTION NUMBER\t(CHARGE DATE)";
		
		return $account_json;
	}
}

function get_accountKR1025_internal($line, &$meta_string)
{
	$panel_text = trim(substr($line, 125, 25)) . " (" . trim(substr($line, 20, 19)) . ")";
	$meta_string = "CARDMEMBER EMBOSSED NAME\t(CARDMEMBER ACCOUNT NUMBER)";
		
	$account_json = array(
		"Collapsible Panel Text" => $panel_text,
		"Basic Control Account Number" => trim(substr($line, 1, 19)),
		"Cardmember Account Number" => trim(substr($line, 20, 19)),
		"Cardmember Balance" => trim(substr($line, 90, 15)),
		"Cardmember Embossed Name" => trim(substr($line, 125, 25))
	);
		
	return $account_json;
}

function get_accountKR1205($line, &$meta_string)
{
	$panel_text = trim(substr($line, 77, 25)) . " (" . trim(substr($line, 62, 15)) . ")";
	$meta_string = "CARDMEMBER NAME\t(CARDMEMBER NUMBER)";
	
	$account_json = array(
		"Collapsible Panel Text" => $panel_text,
		"Request Control Account" => trim(substr($line, 7, 15)),
		"Basic Control Account" => trim(substr($line, 22, 15)),
		"Basic Control Account Name" => trim(substr($line, 37, 25)),
		"Cardmember Number" => trim(substr($line, 62, 15)),
		"Cardmember Name" => trim(substr($line, 77, 25)),
		"Cost Centre" => trim(substr($line, 102, 10)),
		"Employee Identifier" => trim(substr($line, 112, 10)),
		"Company Name" => trim(substr($line, 181, 20)),
		"Address" => trim(substr($line, 201, 25)),
		"City" => trim(substr($line, 226, 18)),
		"State" => trim(substr($line, 244, 2)),
		"Zip" => trim(substr($line, 246, 9)),
		"Country Code/State Code" => trim(substr($line, 256, 3)),
		"Expire Year" => trim(substr($line, 290, 8)),
		"Current Balance" => trim(substr($line, 298, 11))
	);
	
	return $account_json;
}

?>