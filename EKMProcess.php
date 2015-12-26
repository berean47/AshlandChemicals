<?php
/******************************************************************************
 * 
 * Program focus : To import information from EKM Powershop using a .csv file
 * 				   export as input.  Contact information, product information,
 * 				   invoice information and payment information is imported
 * 				   using the infusionsoft api.
 * 
 * Author : John W. Borelli with The Plan B Club, LLC for Rob Drummond and his
 * 			client, Ian Ashland of Ashland Chemicals.
 * 
 * Inputs : All input is extracted via .csv file reads
 * 
 * Outputs: All output will be found in the form of resulting data being
 * 			imported into the appropriate Infusionsoft app.
 * 
******************************************************************************/

// Include required support files
require_once 'Infusionsoft/infusionsoft.php';
require_once 'src/isdk.php';

// Instantiate all required objects
$app=new iSDK();
$products=new Infusionsoft_Product();
$orderitems=new Infusionsoft_OrderItem();
$invoices=new Infusionsoft_Invoice();
$invoiceitems=new Infusionsoft_InvoiceItem();
$invoicepayments=new Infusionsoft_InvoicePayment();
$invoiceservice=new Infusionsoft_InvoiceService();
$contact=new Infusionsoft_Contact();
$contactgroup=new Infusionsoft_ContactGroupAssign();

// Global initializations
$app->cfgCon('yo243','c959c2b28a10eb74a2d5af154ba1986d');
$tName='Product';
$query=array('Id'=>'%');
$limit=1000;
$page=0;
$rFields=array('ShortDescription');
$count=0;
$prodcount=0;
$concount=0;
$found=false;

/******************************************************************************
 * 
 * Pre-load all data that is necessary for comparisons to determine if an item
 * already exists within the Infusionsoft app.
 * 
 * Example : Load all product id's to compare with products being read so that
 * we don't create duplicates of the exact same information
 * 
 *****************************************************************************/

// this loads the EKM product id's from the product ShortDescription field already in IS
$data=$app->dsQuery($tName, $limit, $page, $query, $rFields);
$count=count($data);
foreach ($data as $dat){
	$ekmids[$prodcount]=$dat['ShortDescription'];
	$prodcount++;
}
while ($count===1000){
	$page++;
	$data=$app->dsQuery($tName, $limit, $page, $query, $rFields);
	$count=count($data);
	foreach ($data as $dat){
		$ekmids[$prodcount]=$dat['ShortDescription'];
		$prodcount++;
	}
}

// here we're doing the same thing as above but for the contact's records
$tName='Contact';
$page=0;
$rFields=array('AccountId');
$data=$app->dsQuery($tName, $limit, $page, $query, $rFields);
$count=count($data);
foreach ($data as $dat){
	$ekmcons[$concount]=$dat['AccountId'];
	$concount++;
}
while ($count===1000){
	$page++;
	$data=$app->dsQuery($tName, $limit, $page, $query, $rFields);
	$count=count($data);
	foreach ($data as $dat){
		$ekmcons[$concount]=$dat['AccountId'];
		$concount++;
	}
}

/******************************************************************************
 * 
 * Here we load the data from the .csv file, compare to existing data from
 * within the Infusionsoft app and then update new information into the app
 * remembering to also update our loaded list with new informtation as well.
 * 
 *****************************************************************************/ 

// establish our file stream for .csv reads
$file = fopen("AshlandOrdersLines.csv","r");
// this initial read is to just get past the header information in the .csv file
$order=fgetcsv($file);
/*
 * now we will read through the .csv file line by line and process the information
 * based on what we have loaded and already exists in the Infusionsoft app
 * 
 * Field data is referenced by index as follows:
 * 
 * 		**** These are the indexes for contact information
 * 		0=CustomerId
 * 		10=BillingEmailAddress
 * 		11=BillingFirstName
 * 		12=BillingLastName
 * 		13=BillingCompany
 * 		14=BillingAddress1
 * 		15=BillingAddress2
 * 		16=BillingTown
 * 		17=BillingCounty
 * 		18=BillingCountry
 * 		19=BillingPostalCode
 * 		20=BillingTelephone
 * 		22=ShippingCompany
 * 		23=ShippingFirstName
 * 		24=ShippingLastName
 * 		25=ShippingAddress1
 * 		26=ShippingAddress2
 * 		27=ShippingTown
 * 		28=ShippingCounty
 * 		29=ShippingCountry
 * 		30=ShippingPostalCode
 * 		31=ShippingTelephone
 * 
 * 		**** These are the indexes for product information ****
 * 		39=ProductId
 * 		40=ProductName
 * 		41=ProductCode (mapped to ShortDescription in IS)
 * 		42=ProductOptions
 * 		43=ProductPrice
 * 		44=ProductDiscount
 * 		45=ProductDelivery
 * 		46=ProductQuantity (this would go on an invoice rather than in a product definition)
 * 		47=ProductRRP (Recommended Retail Price)
 * 
 * The $Product->Status fields is set to 1 (active) otherwise the product would
 * import as in-active by default.
 */
while (!feof($file)){
	$order=fgetcsv($file);
	/*
	 * we use index 39 (product id) to test if the line is an actual product.
	 * if index 39 is blank then it is order information and the product
	 * details will be found in successive lines.
	 */
	if ($order[39]!=''){
		/*
		 * now we run a cursive check with existing product id's to prevent duplicates
		 */
		for ($x=0;$x<=$prodcount;$x++){
			if ($ekmids[$x]==$order[39]){$found=true;}
		}
		if ($found!=true){
			// re-create the product object otherwise we are just editing the same record
			$products=new Infusionsoft_Product();
			$productid=$order[39];
			// use ShortDescription to hold the EKM product id
			$products->ShortDescription=$productid;
			$productname=$order[40];
			$products->ProductName=$productname;
			$productcode=$order[41];
			// use the Product->Sku value to store the product code (not the same as the product id)
			$products->Sku=$productcode;
			$productprice=$order[43];
			$products->ProductPrice=$order[43];
			$productdiscount=$order[44];
			// handle product discount here
			$productdelivery=$order[45];
			// no quantity to product but rather to inventory once product is created
			$productquantity=$order[46];
			$productrrp=$order[47];
			// status is set to 1 (active) to keep the product from importing as in-active
			$products->Status=1;
			$products->save();
			$prodcount++;
			$ekmids[$prodcount]=$productid;
		}
		$found=false;
	}
	/*
	 * this is the end of the section that handles updating products with dup check
	 */
	
	/*
	 * 
	 * This begins the contact information based on the product id (index 39) being blank
	 * Just the same, check for a customer number (index 0)
	 * For Infusionsoft purposes, we will store the customer id from ekm into the accountid field
	 * 
	 */
	elseif ($order[39]=='') {
		// now we run a cursive check with existing contact id's to prevent duplicates
		
		/* 		**** These are the indexes for contact information
		* 		0=CustomerId
		* 		10=BillingEmailAddress
		* 		11=BillingFirstName
		* 		12=BillingLastName
		* 		13=BillingCompany
		* 		14=BillingAddress1
		* 		15=BillingAddress2
		* 		16=BillingTown
		* 		17=BillingCounty
		* 		18=BillingCountry
		* 		19=BillingPostalCode
		* 		20=BillingTelephone
		* 		22=ShippingCompany
		* 		23=ShippingFirstName
		* 		24=ShippingLastName
		* 		25=ShippingAddress1
		* 		26=ShippingAddress2
		* 		27=ShippingTown
		* 		28=ShippingCounty
		* 		29=ShippingCountry
		* 		30=ShippingPostalCode
		* 		31=ShippingTelephone
		*/
		
		for ($x=0;$x<=$concount;$x++){
			if ($ekmcons[$x]==$order[0]){$found=true;}
		}
		if ($found!=true){
			// re-create the contact object otherwise we are just editing the same record
			$contact=new Infusionsoft_Contact();
			$contact->addCustomField('_County');
			$contact->addCustomField('_County2');
			$contact->AccountId=$order[0];
			$contact->Email=$order[9];
			$contact->FirstName=$order[10];
			$contact->LastName=$order[11];
			$contact->Company=$order[12];
			$contact->StreetAddress1=$order[13];
			$contact->StreetAddress2=$order[14];
			$contact->City=$order[15];
			$contact->_County=$order[16];
			$contact->Country=$order[17];
			$contact->PostalCode=$order[18];
			$contact->Phone1=$order[19];
			$contact->Address2Street1=$order[21];
			$contact->Address2Street2=$order[22];
			$contact->City2=$order[23];
			$contact->_County2=$order[24];
			$contact->Country2=$order[25];
			$contact->PostalCode2=$order[26];
			$contact->Phone2=$order[27];
			$contact->save();
			$concount++;
			$ekmcons[$concount]=$order[0];
		}
		
	}
	
	
}








fclose($file);
?>