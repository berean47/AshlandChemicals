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
include_once 'EKMConfig.php';
require_once('Infusionsoft/infusionsoft.php');
require_once 'src/isdk.php';

// Instantiate all required objects
$thisapp=new iSDK();
$thisapp->cfgCon($appname,$appkey);
$data=new Infusionsoft_DataService();
/*$order=new Infusionsoft_Job();

$order->JobTitle='This is a test order';
$order->ContactId=50005;
$order->ShipCity='Phoenix';
$order->ShipFirstName='Joe';
$order->ShipLastName='Blow';
$order->ShipPhone='6026168008';
$order->ShipState='AZ';
$order->ShipStreet1='3825 W Anthem Way #3151';
$order->ShipZip='85086';
$order->save();*/
/*
//$order=$thisapp->blankOrder(50005,'This is a test order',$thisapp->infuDate('20151111'),0,0);
$order=$thisapp->dsAdd('Job',array('ContactId'=>50005,'JobTitle'=>'This is a test order'));
//$dat=$thisapp->dsAdd('OrderItem',array('CPU'=>19.99,'PPU'=>19.99,'ItemDescription'=>'Test Order Item','ItemName'=>'Test item name','OrderId'=>$order,'ProductId'=>7031,'Qty'=>2));
$dat=$thisapp->addOrderItem($order,7031,4,19.99,2,'Testing addOrderItem','Testing order item Notes');
echo $order;*/

// establish our file stream for .csv reads
$fname=$_GET['fname'];
if ($fname==''){$fname='AshlandOrdersLines.csv';}
$file = fopen($fname,"r");
// this initial read is to just get past the header information in the .csv file
$order=fgetcsv($file);

while (!feof($file)){
$tax=0;
$shipping=0;
$discount=0;
$subtotal=0;
$total=0;
$order=fgetcsv($file);
//if the first field has an order number then create the order
if ($order[0]!=''){
$tax=$order[4];
$shipping=$order[5];
$discount=$order[6];
$subtotal=$order[7];
$total=$order[8];
$fname=$order[10];
$lname=$order[11];
$email=$order[9];
$company=$order[12];
$add1=$order[13];
$add2=$order[14];
$country=$order[17];
$zip=$order[18];
$phone=$order[19];
$city=$order[15];
$state=$order[16];

// if this is a new order then get the contact by querying the account id field for the EKM customer Id
$con=$data->query('Contact',array('AccountId'=>$order[0]),1,0);

/* the condition below tests to see if the billing address is the same as the shipping address and if TRUE is the
result then that means no and we should use the specific shipping fields otherwise use the billing information
*/
if ($order[20]=='TRUE'){
    $company=$order[21];
    $fname=$order[22];
    $lname=$order[23];
    $add1=$order[24];
    $add2=$order[25];
    $city=$order[26];
    $state=$order[27];
    $country=$order[28];
    $zip=$order[29];
}
$invoice=$thisapp->blankOrder($con[0]['Id'],'EKM imported order.',date('Ymd'),0,0);

//$invoice=$thisapp->dsAdd('Job',array('ContactId'=>$con[0]['Id'],'JobTitle'=>'EKM imported order for order number ('.$order[2].')','ShipCity'=>$city,'ShipCompany'=>$company,'ShipCountry'=>$country,'ShipFirstName'=>$fname,'ShipLastName'=>$lname,'ShipPhone'=>$phone,'ShipState'=>$state,'ShipStreet1'=>$add1,'ShipStreet2'=>$add2,'ShipZip'=>$zip));
//echo 'Adding  order for contact id : '.$data[0]['Id'].'<br>';
} else {
//if this is not a new order then add order items to the existing order

/**** These are the indexes for product information ****
 * 		39=ProductId
 * 		40=ProductName
 * 		41=ProductCode (mapped to ShortDescription in IS)
 * 		42=ProductOptions
 * 		43=ProductPrice
 * 		44=ProductDiscount
 * 		45=ProductDelivery
 * 		46=ProductQuantity (this would go on an invoice rather than in a product definition)
 * 		47=ProductRRP (Recommended Retail Price)*/

$prod=$thisapp->dsQuery('Product',1,0,array('ShortDescription'=>$order[39]),array('Id'));
$dat=$thisapp->addOrderItem($invoice,$prod[0]['Id'],4,$prod[43],$order[46],$order[40],'EKM imported invoice item');
echo 'Adding produce ('.$prod[0]['Id'].') for order number '.$invoice.'<br />';
}
}
$orderDate = substr($order[3],1,8);
$pDate = $app->infuDate($orderDate);
$dat=$thisapp->addOrderItem($invoice,0,4,-$discount,1,'Total Discount','EKM imported invoice item');
$dat=$thisapp->addOrderItem($invoice,0,1,$shipping,1,'Total Shipping Cost','EKM imported invoice item');
$dat=$thisapp->manualPmt($invoice,$total,$pDate,'Manual Credit Card Payment','EKM imported payment',true);
fclose($file);
