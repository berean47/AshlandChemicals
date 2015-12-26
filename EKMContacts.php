<?php
/******************************************************************************
 * 
 * Program focus : To import information from EKM Powershop using a .csv file
 * 				   export as input.  Contact information, product information,
 * 				   invoice information and payment information is imported
 * 				   using the infusionsoft api.
 * 
 * Author : John W. Borelli with The Plan B Club, LLC for Rob Drummond and his
 * 			client, Ian Ashfield of Ashland Chemicals.
 * 
 * Inputs : All input is extracted via .csv file reads
 * 
 * Outputs: All output will be found in the form of resulting data being
 * 			imported into the appropriate Infusionsoft app.
 * 
******************************************************************************/

// Include required support files
include_once 'EKMConfig.php';
require_once 'Infusionsoft/infusionsoft.php';
require_once 'src/isdk.php';

// Instantiate all required objects
$app=new iSDK();
$eml=new Infusionsoft_EmailService();
$contact=new Infusionsoft_Contact();
$contactgroup=new Infusionsoft_ContactGroupAssign();


// Global initializations
$app->cfgCon($appname,$appkey);
$tName='Contact';
$query=array('Id'=>'%');
$limit=1000;
$page=0;
$rFields=array('Email','Id');
$count=0;
$prodcount=0;
$concount=0;
$found=false;
$sel=-1;

/******************************************************************************
 * 
 * Pre-load all data that is necessary for comparisons to determine if an item
 * already exists within the Infusionsoft app.
 * 
 * Example : Load all product id's to compare with products being read so that
 * we don't create duplicates of the exact same information
 * 
 *****************************************************************************/

// here we're doing the same thing as above but for the contact's records
// how many rows in ContactGroup table
$count=$app->dsCount($tName,$query);
// iterate all rows of table
while ($count>0){
    $data=$app->dsQuery($tName,$limit,$page,$query,$rFields);
    foreach ($data as $dat){
        $ekmcons[$concount]=$dat['Email'];
        $ekmids[$concount]=$dat['Id'];
    }
    $count=$count-1000;
    $page++;
}
$concount=$app->dsCount($tName,$query);


/*$data=$app->dsQuery($tName, $limit, $page, $query, $rFields);
$count=count($data);
foreach ($data as $dat){
	$ekmcons[$concount]=$dat['Email'];
    $ekmids[$concount]=$dat['Id'];
	$concount++;
}
while ($count===1000){
	$page++;
	$data=$app->dsQuery($tName, $limit, $page, $query, $rFields);
	$count=count($data);
	foreach ($data as $dat){
		$ekmcons[$concount]=$dat['Email'];
        $ekmids[$concount]=$dat['Id'];
		$concount++;
	}
}*/

/******************************************************************************
 * 
 * Here we load the data from the .csv file, compare to existing data from
 * within the Infusionsoft app and then update new information into the app
 * remembering to also update our loaded list with new information as well.
 * 
 *****************************************************************************/ 

// establish our file stream for .csv reads
$fname=$_GET['fname'];
if ($fname==''){$fname='AshlandOrdersLines.csv';}
$file = fopen($fname,"r");
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
 * 		09=BillingEmailAddress
 * 		10=BillingFirstName
 * 		11=BillingLastName
 * 		12=BillingCompany
 * 		13=BillingAddress1
 * 		14=BillingAddress2
 * 		15=BillingTown
 * 		16=BillingCounty
 * 		17=BillingCountry
 * 		18=BillingPostalCode
 * 		19=BillingTelephone
 * 		20=DifferentAddress ('TRUE' or 'FALSE')
 * 		21=ShippingCompany
 * 		22=ShippingFirstName
 * 		23=ShippingLastName
 * 		24=ShippingAddress1
 * 		25=ShippingAddress2
 * 		26=ShippingTown
 * 		27=ShippingCounty
 * 		28=ShippingCountry
 * 		29=ShippingPostalCode
 * 		30=ShippingTelephone
 *         35=Status
 * 
 * The $Product->Status fields is set to 1 (active) otherwise the product would
 * import as in-active by default.
 */
$t1=$app->dsQuery('ContactGroup',1,0,array('GroupName'=>'FailedTransaction'),array('Id'));
$t2=$app->dsQuery('ContactGroup',1,0,array('GroupName'=>'NewEKM'),array('Id'));

while (!feof($file)){
	$order=fgetcsv($file);
	/*we use index 0 (ekm account id) to test if the line is an actual contact.
	 * if index 0 is not blank then it is account/contact information and the contact
	 * details will be found in successive lines.*/
	if ($order[0]!=''){
		// now we run a cursive check with existing ekm account id's to prevent duplicates
		for ($x=0;$x<=$concount;$x++){
			if ($ekmids[$x]==$order[0]){$found=true;$sel=$x;}
		}
		if ($found!=true){
			if (($order[9]!='') && (strpos('EKMUNIQUE',$order[9])==0)  && ($order[36]=='COMPLETE')){
				//echo 'updating for email address '.$order[9].'<br>';
				$contact=new Infusionsoft_Contact();
				$eml=new Infusionsoft_EmailService();
				$grp=new Infusionsoft_ContactGroupAssign();
				$data=new Infusionsoft_DataService();
				$contact->addCustomField('_County');
				$contact->addCustomField('_County2');
				$contact->addCustomField('_ShippingCompany');
				$contact->addCustomField('_ShippingFirstName');
				$contact->addCustomField('_ShippingLastName');
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
				$contact->Leadsource='EKM Import';
				if ($order[20]=='TRUE'){
					$contact->_ShippingCompany=$order[21];
					$contact->_ShippingFirstName=$order[22];
					$contact->_ShippingLastName=$order[23];
					$contact->Address2Street1=$order[24];
					$contact->Address2Street2=$order[25];
					$contact->City2=$order[26];
					$contact->_County2=$order[27];
					$contact->Country2=$order[28];
					$contact->PostalCode2=$order[29];
					$contact->Phone2=$order[30];
				} else {
					$contact->_ShippingCompany=$order[12];
					$contact->_ShippingFirstName=$order[10];
					$contact->_ShippingLastName=$order[11];
					$contact->Address2Street1=$order[13];
					$contact->Address2Street2=$order[14];
					$contact->City2=$order[15];
					$contact->_County2=$order[16];
					$contact->Country2=$order[17];
					$contact->PostalCode2=$order[18];
					$contact->Phone2=$order[19];
				}
                if ($order[9]>'') {
                    $test=$app->findByEmail($contact->Email,array('Id'));

                    if ($test[0]['Id']<1) {
                        $contact->save();
                        $d = $app->dsQuery('Contact', 1, 0, array('AccountId' => $order[0]), array('Id'));
                        $eml->optIn($contact->Email, 'API Import of existing EKM Powershop customers.');
                        $app->grpAssign($d[0]['Id'], $t2[0]['Id']);
                        $app->grpAssign($d[0]['Id'],123);  // G4LOrdersDates
                    }
                }
				}
			}
		}
		$found=false;

	/*
	 * this is the end of the section that handles updating contacts with dup check
	 */
}

fclose($file);
?>