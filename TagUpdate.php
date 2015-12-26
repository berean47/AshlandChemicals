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
		if ($order[9]!='') {
			$d = $app->dsQuery( 'Contact', 1, 0, array( 'Email' => $order[9] ), array( 'Id' ) );
			echo 'Found a match for EKM Email '.$order[9].'/ IS contact Id '.$d[0]['Id'].'<br />';
			$app->grpAssign( $d[0]['Id'], $t2[0]['Id'] );
			echo 'Applying group id '.$t2[0]['Id'].' to contact id '.$d[0]['Id'].'<br />';
			$app->grpAssign( $d[0]['Id'], 121 );  // WC4LOrdersDates
			echo 'Applying group id 123 to contact id '.$d[0]['Id'].'<br /><br />';
		}
    	}



fclose($file);
?>