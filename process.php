<?php
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
	
	$account_json = array(
		"Account Number" => (string)($account->attributes()->AccountNumber),
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
		
		$transaction_json = array(
			"Processor Transaction ID" => (string)($financial_transaction->ProcessorTransactionId),
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

/*
$divCounter = 0;	
$AccountEntityArray = $xml->IssuerEntity->CorporateEntity->AccountEntity;
foreach($AccountEntityArray as $foo){
			
	$divCounter ++;
	print "<pre> ------- CARD ACCOUNT: " . $divCounter . "--------" . PHP_EOL;
	print "AccountNumber: " . $foo->attributes()->AccountNumber . "<br>"; 	//['AccountNumber']
						
	print "SequenceNum: " . $foo->AccountInformation_4300->HierarchyRecordHeader->SequenceNum . "<br>"; 
	print "AccountTypeCode: " . $foo->AccountInformation_4300->AccountTypeCode . "<br>";
	print "EffectiveDate: " . $foo->AccountInformation_4300->EffectiveDate . "<br>";
	print "ExpirationDate: " . $foo->AccountInformation_4300->ExpirationDate . "<br>";
	print "ExpirationDate: " . $foo->AccountInformation_4300->ExpirationDate . "<br>";
	print "EmployeeId: " . $foo->AccountInformation_4300->EmployeeId . "<br>";
	print "NameLine1: " . $foo->AccountInformation_4300->NameLine1 . "<br>";

	print "AddressLine: " . $foo->HierarchyAddress_4410->AddressLine . "<br>";
	print "City: " . $foo->HierarchyAddress_4410->City . "<br>";
	print "StateProvince: " . $foo->HierarchyAddress_4410->StateProvince . "<br>";
	print "CountryCode: " . $foo->HierarchyAddress_4410->CountryCode . "<br>";
	print "PostalCode: " . $foo->HierarchyAddress_4410->PostalCode . "<br>";			
	
	$subDivCounter = 0;
	$FinTransEntity = $foo->FinancialTransactionEntity;
	foreach ($FinTransEntity as $bar){
				
		$subDivCounter ++;
		print "<br>" . " ------ CARD TRANSACTION: " . $subDivCounter . " ------- " . PHP_EOL ;
				
		print "ProcessorTransactionId: " . $bar->FinancialTransaction_5000->ProcessorTransactionId . "<br>";
		print "MasterCardFinancialTransactionId: " . $bar->FinancialTransaction_5000->MasterCardFinancialTransactionId . "<br>"; 
		print "AcquirerReferenceData: " . $bar->FinancialTransaction_5000->AcquirerReferenceData . "<br>";
		print "CardHolderTransactionType: " . $bar->FinancialTransaction_5000->CardHolderTransactionType . "<br>";
		print "PostingDate: " . $bar->FinancialTransaction_5000->PostingDate . "<br>";
		print "ProcessingDate: " . $bar->FinancialTransaction_5000->ProcessingDate . "<br>";
		print "DebitOrCreditIndicator: " . $bar->FinancialTransaction_5000->DebitOrCreditIndicator . "<br>";
		print "AmountInOriginalCurrency: " . $bar->FinancialTransaction_5000->AmountInOriginalCurrency . "<br>";
		print "PostedCurrencyCode: " . $bar->FinancialTransaction_5000->PostedCurrencyCode . "<br>";
		print "CustomIdentifier: " . $bar->FinancialTransaction_5000->CustomIdentifier . "<br>";
				
		print "CustomerRefValue1: " . $bar->FinancialTransaction_5000->CustomerRefValue1 . "<br>";
		print "CustomerRefValue2: " . $bar->FinancialTransaction_5000->CustomerRefValue2 . "<br>";
		print "CustomerRefValue3: " . $bar->FinancialTransaction_5000->CustomerRefValue3 . "<br>";
		print "CustomerRefValue4: " . $bar->FinancialTransaction_5000->CustomerRefValue4 . "<br>";
		print "CustomerRefValue5: " . $bar->FinancialTransaction_5000->CustomerRefValue5 . "<br>";
		print "CustomerRefValue6: " . $bar->FinancialTransaction_5000->CustomerRefValue6 . "<br>";
		print "CustomerRefValue7: " . $bar->FinancialTransaction_5000->CustomerRefValue7 . "<br>";
		print "CustomerRefValue8: " . $bar->FinancialTransaction_5000->CustomerRefValue8 . "<br>";
		print "CustomerRefValue9: " . $bar->FinancialTransaction_5000->CustomerRefValue9 . "<br>";
		print "CustomerRefValue10: ". $bar->FinancialTransaction_5000->CustomerRefValue10 . "<br>";
				
		//TODO TotalTaxAmount, CustomerVATNum, MemoFlag
		print "TransactionCategoryIndicator: " . $bar->FinancialTransaction_5000->TransactionCategoryIndicator . "<br>";
										
		print "CardAcceptorId: " . $bar->CardAcceptor_5001->CardAcceptorId . "<br>";
		print "CardAcceptorName: " . $bar->CardAcceptor_5001->CardAcceptorName . "<br>";
		print "CardAcceptorStreetAddress: " . $bar->CardAcceptor_5001->CardAcceptorStreetAddress . "<br>";
		print "CardAcceptorStateProvince: " . $bar->CardAcceptor_5001->CardAcceptorStateProvince . "<br>";
		print "CardAcceptorLocationPostalCode: " . $bar->CardAcceptor_5001->CardAcceptorLocationPostalCode . "<br>";
		print "CardAcceptorCountryCode: " . $bar->CardAcceptor_5001->CardAcceptorCountryCode . "<br>";
		print "CardAcceptorTelephoneNum: " . $bar->CardAcceptor_5001->CardAcceptorTelephoneNum . "<br>";
		print "CardAcceptorBusinessCode: " . $bar->CardAcceptor_5001->CardAcceptorBusinessCode . "<br>";
				
		//TODO DUNNum, AustinTetraNum, CardAcceptorNAICSNum
		print "CardAcceptorTaxIdIndicator: " . $bar->CardAcceptor_5001->CardAcceptorTaxIdIndicator . "<br>";
				
		//TODO CardAcceptorTaxId, CardAcceptorVATNum	
	}
}
*/
?>