<?php
// loads the file, deletes it and processes its content
function process_mastercard($target_file_path)
{
	// BUILD AN OBJECT
	libxml_use_internal_errors(true);
	$feed_file = file_get_contents($target_file_path, true, NULL);

	// deserialize
	$xml = simplexml_load_string($feed_file);

	// delete the file as it's no longer needed
	if (!unlink($target_file_path))
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
	$accounts_json_array = array();

	// find account entities
	foreach ($xml->IssuerEntity as $issuer_entity)
	{
		foreach ($issuer_entity->CorporateEntity as $corporate_entity)
		{
			// go through corporate-level accounts
			foreach ($corporate_entity->AccountEntity as $account_entity)
			{
				array_push($accounts_json_array, GetAccountJson($account_entity));
			}
			
			// go through organization-level accounts
			foreach ($corporate_entity->OrganizationPointEntity as $organization_point_entity)
			{
				foreach ($organization_point_entity->AccountEntity as $account_entity)
				{
					array_push($accounts_json_array, GetAccountJson($account_entity));
				}
			}
		}
	}

	$result_json_array = array(
		"Error" => "",
		"Accounts" => $accounts_json_array
	);

	$result_json = json_encode($result_json_array);
	
	return $result_json;
}

// handles the account record and creates a JSON out of it
function GetAccountJson($account_entity)
{
	$account_info = $account_entity->AccountInformation_4300;
	$hierarchy_addr = $account_entity->HierarchyAddress_4410;
		
	// prepare a string that will be shown in the header panel
	$obscured_account_number = " (*" . substr((string)($account_entity->attributes()->AccountNumber), -4) . ")";
	$account_panel_text = (string)($account_info->NameLine1) . $obscured_account_number;
	
	$account_json = array(
		"Account Panel Text" => $account_panel_text,
		//"Account Number" => (string)($account_entity->attributes()->AccountNumber),
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
	
	$transactions_json_array = array();
	
	foreach ($account_entity->FinancialTransactionEntity as $transaction)
	{
		$financial_transaction = $transaction->FinancialTransaction_5000;
		$card_acceptor = $transaction->CardAcceptor_5001;
		
		// prepare a string that will be shown in the header panel
		$transaction_panel_text = (string)($financial_transaction->ProcessorTransactionId) . " (" . (string)($financial_transaction->PostingDate) . ")";
		
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
			"Total Tax Amount" => (string)($financial_transaction->TotalTaxAmount),
			"Customer VAT Number" => (string)($financial_transaction->CustomerVATNum),
			"Memo Flag" => (string)($financial_transaction->MemoFlag),
			"Transaction Category Indicator" => (string)($financial_transaction->TransactionCategoryIndicator),
			
			"Card Acceptor ID" => (string)($card_acceptor->CardAcceptorId),
			"Card Acceptor Name" => (string)($card_acceptor->CardAcceptorName),
			"Card Acceptor Street Address" => (string)($card_acceptor->CardAcceptorStreetAddress),
			"Card Acceptor State Province" => (string)($card_acceptor->CardAcceptorStateProvince),
			"Card Acceptor Location Postal Code" => (string)($card_acceptor->CardAcceptorLocationPostalCode),
			"Card Acceptor Country Code" => (string)($card_acceptor->CardAcceptorCountryCode),
			"Card Acceptor Telephone Number" => (string)($card_acceptor->CardAcceptorTelephoneNum),
			"Card Acceptor Business Code" => (string)($card_acceptor->CardAcceptorBusinessCode),
			"DUN Number" => (string)($card_acceptor->DUNNum),
			"Austin Tetra Number" => (string)($card_acceptor->AustinTetraNum),
			"Card Acceptor NAICS Number" => (string)($card_acceptor->CardAcceptorNAICSNum),
			"Card Acceptor Tax ID Indicator" => (string)($card_acceptor->CardAcceptorTaxIdIndicator),
			"Card Acceptor Tax ID" => (string)($card_acceptor->CardAcceptorTaxId),
			"Card Acceptor VAT Number" => (string)($card_acceptor->CardAcceptorVATNum)
		);
		
		array_push($transactions_json_array, $transaction_json);
	}
	
	$account_json["Transactions"] = $transactions_json_array;
	
	return $account_json;
}
?>