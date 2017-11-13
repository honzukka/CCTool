// generates html code with collapsibles based on the json and returns it
function buildView(objJson)
{
	switch ($("#seltype").val())
	{
		case "mc":
			return buildViewMastercard(objJson);
		case "v40":
			return buildViewVisa(objJson);
		case "v44":
			return buildViewVisa(objJson);
		case "amexGL1025":
			return buildViewAmexGL1025(objJson);
		case "amexGL1205":
			return buildViewMastercard(objJson);
		case "amexTMKD":
			return buildViewAmexGL1025(objJson);
		case "amexGL1080":
			return buildViewAmexGL1025(objJson);
		case "amexKR1025":
			return buildViewMastercard(objJson);
			return 
		default:
			return "error";
	}
}

// generates html code with collapsibles based on sections in a Mastercard file
function buildViewMastercard(objJson)
{
	// HTML 01 - open a panel group for the view
	var viewHTML = "<div class='panel-group'>";
	
	// HTML 02 - each section is shown in a collapsible panel
	viewHTML += GetCollapsibleHeaderPanelOpening("collapseCardholders", "Accounts (" + Object.keys(objJson.Accounts).length + ")", "panel-primary");
	viewHTML += "<li class='list-group-item'><h4><em>" + objJson.AccountsMeta + "</em></h4></li>";
	viewHTML += buildViewSection(objJson.Accounts, "Accounts");
	viewHTML += GetCollapsibleHeaderPanelClosing();
	
	// HTML 01 - close the panel group
	viewHTML += "</div>";
	
	return viewHTML;
}

// generates html code with collapsibles based on sections in a Visa file
function buildViewVisa(objJson)
{
	// HTML 01 - open a panel group for the view
	var viewHTML = "<div class='panel-group'>";
	
	// HTML 02 - each section is shown in a collapsible panel
	viewHTML += GetCollapsibleHeaderPanelOpening("collapseCardholders", "Cardholders (" + Object.keys(objJson.Cardholders).length + ")", "panel-primary");
	viewHTML += "<li class='list-group-item'><h4><em>" + objJson.CardholdersMeta + "</em></h4></li>";
	viewHTML += buildViewSection(objJson.Cardholders, "Cardholders");
	viewHTML += GetCollapsibleHeaderPanelClosing();
	
	viewHTML += GetCollapsibleHeaderPanelOpening("collapseAccounts", "Accounts (" + Object.keys(objJson.Accounts).length + ")", "panel-primary");
	viewHTML += "<li class='list-group-item'><h4><em>" + objJson.AccountsMeta + "</em></h4></li>";
	viewHTML += buildViewSection(objJson.Accounts, "Accounts");
	viewHTML += GetCollapsibleHeaderPanelClosing();
	
	viewHTML += GetCollapsibleHeaderPanelOpening("collapseTransactions", "Transactions (" + Object.keys(objJson.Transactions).length + ")", "panel-primary");
	viewHTML += "<li class='list-group-item'><h4><em>" + objJson.TransactionsMeta + "</em></h4></li>";
	viewHTML += buildViewSection(objJson.Transactions, "Transactions");
	viewHTML += GetCollapsibleHeaderPanelClosing();
	
	// HTML 01 - close the panel group
	viewHTML += "</div>";
	
	return viewHTML;
}

// generates html code with collapsibles based on sections in a GL1025 file
function buildViewAmexGL1025(objJson)
{
	// HTML 01 - open a panel group for the view
	var viewHTML = "<div class='panel-group'>";
	
	// HTML 02 - each section is shown in a collapsible panel
	viewHTML += GetCollapsibleHeaderPanelOpening("collapseCardholders", "Transactions (" + Object.keys(objJson.Transactions).length + ")", "panel-primary");
	viewHTML += "<li class='list-group-item'><h4><em>" + objJson.TransactionsMeta + "</em></h4></li>";
	viewHTML += buildViewSection(objJson.Transactions, "Transactions");
	viewHTML += GetCollapsibleHeaderPanelClosing();
	
	// HTML 01 - close the panel group
	viewHTML += "</div>";
	
	return viewHTML;
}

// generates html code for a section (i.e. a list of objects containing fields and optionally a Transactions section)
// see expected JSON format below
function buildViewSection(section, sectionName)
{
	var viewHTML = "";
	
	var itemCounter = 1;
	for (var item in section)
	{
		// HTML 03 - each account header is shown in a collapsible panel
		var panelId = "collapse" + sectionName + itemCounter;
		var panelText = section[item]["Collapsible Panel Text"];
		var itemViewHTML = GetCollapsibleHeaderPanelOpening(panelId, panelText, "panel-default");

		// insert account items into the collapsible area of the account header panel
		$.each(section[item], function(itemName, itemValue){
		
			// add simple items
			if (itemName != "Transactions" && itemName != "Line Items" &&
				itemName != "NestedMeta" &&
				itemName != "Collapsible Panel Text" && itemValue != "")
			{
				itemViewHTML  += "<li class='list-group-item'><strong>" + itemName + "</strong>: <mark>" + itemValue +  "</mark></li>";
			}
			// nested items have another collapsible header panel
			else if (itemName == "Transactions" || itemName == "Line Items")
			{
				// HTML 04
				itemViewHTML  += "<li class='list-group-item'>";
				
				// HTML 05 - open the collapsible header panel for nested items
				var panelId = "nestedCollapse" + itemCounter;
				var panelText = itemName + " (" + Object.keys(itemValue).length + ")";
				itemViewHTML  += GetCollapsibleHeaderPanelOpening(panelId, panelText, "panel-primary");
				itemViewHTML += "<li class='list-group-item'><h4><em>" + section[item]["NestedMeta"] + "</em></h4></li>";

				var nestedItemCounter = 1;
				for (var nestedItem in itemValue)
				{
					// HTML 06 - each nested item has another collapsible panel header
					var panelId = "nestedCollapse" + itemCounter + "-" + nestedItemCounter;
					var panelText = itemValue[nestedItem]["Collapsible Panel Text"];
					itemViewHTML  += GetCollapsibleHeaderPanelOpening(panelId, panelText, "panel-default");

					// insert nested items into the collapsible area of each nested item header panel
					$.each(itemValue[nestedItem], function(nestedItemName, nestedItemValue){
						if (nestedItemName != "Collapsible Panel Text" && nestedItemValue != "")
						{
							itemViewHTML  += "<li class='list-group-item'><strong>" + nestedItemName + "</strong>: <mark>" + nestedItemValue +  "</mark></li>";
						}
					});
					
					// HTML 06 - close the collapsible header panel for each nested item
					itemViewHTML  += GetCollapsibleHeaderPanelClosing();
					nestedItemCounter++;
				}
				
				// HTML 05 - close the transactions header panel
				itemViewHTML  += GetCollapsibleHeaderPanelClosing();
				
				// HTML 04
				itemViewHTML  += "</li>";
			}
		});
		
		// HTML 03 - close the account header panel
		itemViewHTML += GetCollapsibleHeaderPanelClosing();
		
		viewHTML += itemViewHTML;
		itemCounter++;
	}
	
	return viewHTML;
}

function GetCollapsibleHeaderPanelOpening(panelId, panelText, panelClassString)
{
	return "" +
		"<div class='panel " + panelClassString + "'>" +
			"<div class='panel-heading'>" +
				"<p class='panel-title'>" +
					"<a data-toggle='collapse' href='#" + panelId + "'>" + panelText + "</a>" +
				"</p>" +
			"</div>" +
			"<div id='" + panelId + "' class='panel-collapse collapse'>" +
				"<ul class='list-group'>";
}

function GetCollapsibleHeaderPanelClosing()
{
	return "</ul>" + "</div>" + "</div>";
}

/* EXPECTED JSON FORMAT
Mastercard:		Error, Accounts(+Transactions)
Visa:			Error, Accounts, Cardholders, Transactions
Amex GL1025:	Error, Transactions
Amex GL1205:	Error, Accounts
Amex TMKD:		Error, Transactions
Amex GL1080:	Error, Transactions(+Line Items)
Amex KR1025:	Error, Accounts(+Transactions)

{
	"Error": "error_message",   <--- error_message is empty if no error has occured
	"Accounts": [
		{ 
			"Collapsible Panel Text": "header text",
			"Item1": "Item1Value",
			...,
			
			***** NESTED TRANSACTIONS ONLY FOR MASTERCARD *****
			
			"Transactions": [ 
				{ 
					"Collapsible Panel Text": "text",
					"Item1": "Item1Value",
					...
				},
				... 
			],
			"NestedMeta": "meta_string"	<--- this string describes the format of collapsible panel texts
		},
		...
	],
	"AccountsMeta": "meta_string",	<--- this string describes the format of collapsible panel texts
	
	"Cardholders": [
		{
			"Collapsible Panel Text": "header text",
			"Item1": "Item1Value",
			...
		}
	],
	"CardholdersMeta": "meta_string",	<--- this string describes the format of collapsible panel texts
	
	"Transactions": [
		{
			"Collapsible Panel Text": "header text",
			"Item1": "Item1Value",
			...,
			
			***** NESTED ITEMIZATION ONLY FOR AMEX GL1080 *****
			
			"Line Items": [
				{
					"Collapsible Panel Text": "text",
					"Item1": "Item1Value",
					...
				},
				...
			],
			"NestedMeta": "meta_string"	<--- this string describes the format of collapsible panel texts
		}
	],
	"TransactionsMeta": "meta_string",	<--- this string describes the format of collapsible panel texts
}
*/