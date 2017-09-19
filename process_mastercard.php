<?php
// this supresses warning messages (unlink prints warning messages so an "invalid json" is sent to the html page)
// error messages are still on
error_reporting(E_ALL ^ E_WARNING);

// UPLOAD
$target_dir = "uploads/";
$target_file = $target_dir . basename( $_FILES["fileUpload"]["name"]);
$uploadOk = 1;

if (!move_uploaded_file($_FILES["fileUpload"]["tmp_name"], $target_file)) 
{
	$response_array = array("Error" => "There was an error uploading your file.");
	$response_json = json_encode($response_array);
	print $response_json;
	exit;
}

// BUILD AN OBJECT
libxml_use_internal_errors(true);
$feed_file = file_get_contents($target_file, true, NULL);

// deserialize
$xml = simplexml_load_string($feed_file);

// delete the file as it's no longer needed
if (!unlink($target_file))
{
	$response_array = array("Error" => "There was an error while handling the file. Please contact server administrator about this.");
	$response_json = json_encode($response_array);
	print $response_json;
	exit;
}

// if the deserialization failed, print error messages and exit
if ($xml === false)
{
	$error_message = "Failed loading XML:" . PHP_EOL;
	foreach(libxml_get_errors() as $error)
	{
		$error_message = $error_message . $error->message . PHP_EOL;
	}
	$response_array = array("Error" => $error_message);
	$response_json = json_encode($response_array);
	print $response_json;
	exit;
}

// BUILD REDUCED JSON
$account_entities_array = $xml->IssuerEntity->CorporateEntity->AccountEntity;
$accounts_json_array = array();

foreach ($account_entities_array as $account)
{
	$account_info = $account->AccountInformation_4300;
	$hierarchy_addr = $account->HierarchyAddress_4410;
	
	// prepare a string that will be shown in the header panel
	$obscured_account_number = " (*" . substr((string)($account->attributes()->AccountNumber), -4) . ")";
	$account_panel_text = (string)($account_info->NameLine1) . $obscured_account_number;
	
	$account_json = array(
		"Account Panel Text" => $account_panel_text,
		//"Account Number" => (string)($account->attributes()->AccountNumber),
		"Account Type Code" => (string)($account_info->AccountTypeCode),
		"Effective Date" => (string)($account_info->EffectiveDate),
		"Expiration Date" => (string)($account_info->ExpirationDate),
		"Employee ID" => (string)($account_info->EmployeeId),
		"NameLine1" => (string)($account_info->NameLine1),
		
		"Address Line" => (string)($hierarchy_addr->AddressLine),
		"City" => (string)($hierarchy_addr->City),
		"State Province" => (string)($hierarchy_addr->StateProvince),
		"Country Code" => (string)($hierarchy_addr->CountryCode),
		"Postal Code" => (string)($hierarchy_addr->PostalCode)
	);
	
	$financial_transactions_array = $account->FinancialTransactionEntity;
	$transactions_json_array = array();
	
	foreach ($financial_transactions_array as $transaction)
	{
		$financial_transaction = $transaction->FinancialTransaction_5000;
		$card_acceptor = $transaction->CardAcceptor_5001;
		
		// prepare a string that will be shown in the header panel
		$transaction_panel_text = (string)($financial_transaction->ProcessorTransactionId);
		
		$transaction_json = array(
			"Transaction Panel Text" => $transaction_panel_text,
			//"Processor Transaction ID" => (string)($financial_transaction->ProcessorTransactionId),
			"MasterCard Financial Transaction ID" => (string)($financial_transaction->MasterCardFinancialTransactionId),
			"Acquirer Reference Data" => (string)($financial_transaction->AcquirerReferenceData),
			"Card Holder Transaction Type" => (string)($financial_transaction->CardHolderTransactionType),
			"Posting Date" => (string)($financial_transaction->PostingDate),
			"ProcessingDate" => (string)($financial_transaction->ProcessingDate),
			"Debit Or Credit Indicator" => (string)($financial_transaction->DebitOrCreditIndicator),
			"Amount In Original Currency" => (string)($financial_transaction->AmountInOriginalCurrency),
			"Posted Currency Code" => (string)($financial_transaction->PostedCurrencyCode),
			"Custom Identifier" => (string)($financial_transaction->CustomIdentifier),
			"Customer Ref Value 1" => (string)($financial_transaction->CustomerRefValue1),
			"Customer Ref Value 2" => (string)($financial_transaction->CustomerRefValue2),
			"Customer Ref Value 3" => (string)($financial_transaction->CustomerRefValue3),
			"Customer Ref Value 4" => (string)($financial_transaction->CustomerRefValue4),
			"Customer Ref Value 5" => (string)($financial_transaction->CustomerRefValue5),
			"Customer Ref Value 6" => (string)($financial_transaction->CustomerRefValue6),
			"Customer Ref Value 7" => (string)($financial_transaction->CustomerRefValue7),
			"Customer Ref Value 8" => (string)($financial_transaction->CustomerRefValue8),
			"Customer Ref Value 9" => (string)($financial_transaction->CustomerRefValue9),
			"Customer Ref Value 10" => (string)($financial_transaction->CustomerRefValue10),
			//TODO TotalTaxAmount, CustomerVATNum, MemoFlag
			"Transaction Category Indicator" => (string)($financial_transaction->TransactionCategoryIndicator),
			
			"Card Acceptor ID" => (string)($card_acceptor->CardAcceptorId),
			"Card Acceptor Name" => (string)($card_acceptor->CardAcceptorName),
			"Card Acceptor Street Address" => (string)($card_acceptor->CardAcceptorStreetAddress),
			"Card Acceptor State Province" => (string)($card_acceptor->CardAcceptorStateProvince),
			"Card Acceptor Location Postal Code" => (string)($card_acceptor->CardAcceptorLocationPostalCode),
			"Card Acceptor Country Code" => (string)($card_acceptor->CardAcceptorCountryCode),
			"Card Acceptor Telephone Number" => (string)($card_acceptor->CardAcceptorTelephoneNum),
			"Card Acceptor Business Code" => (string)($card_acceptor->CardAcceptorBusinessCode),
			//TODO DUNNum, AustinTetraNum, CardAcceptorNAICSNum
			"Card Acceptor Tax ID Indicator" => (string)($card_acceptor->CardAcceptorTaxIdIndicator)
			//TODO CardAcceptorTaxId, CardAcceptorVATNum
		);
		
		array_push($transactions_json_array, $transaction_json);
	}
	
	$account_json["Transactions"] = $transactions_json_array;
	array_push($accounts_json_array, $account_json);
}

$result_json_array = array(
	"Error" => "",
	"Accounts" => $accounts_json_array
);

$result_json = json_encode($result_json_array);

// OUTPUT
print $result_json;
?>