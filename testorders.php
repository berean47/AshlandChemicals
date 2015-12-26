<?php
/**
 * Created by PhpStorm.
 * User: JohnBorelli
 * Date: 12/22/2015
 * Time: 9:57 AM
 */

include_once 'EKMConfig.php';
require_once('src/isdk.php');
$thisapp=new iSDK();
$thisapp->cfgCon($appname,$appkey);

$fname=$_GET['fname'];
if ($fname==''){$fname='AshlandOrdersLines.csv';}
$file = fopen($fname,"r");
// this initial read is to just get past the header information in the .csv file
$order=fgetcsv($file);

while (!feof($file)) {
    $tax = 0;
    $shipping = 0;
    $discount = 0;
    $subtotal = 0;
    $total = 0;
    $order = fgetcsv($file);
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
//$con=$data->query('Contact',array('AccountId'=>$order[0]),1,0);
$con=$thisapp->dsQuery('Contact',1000,0,array('AccountId'=>$order[0]),array('Id'));

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
$iId=$thisapp->blankOrder($con[0]['Id'],'testing it out', $thisapp->infuDate($order[3]),0,0);
}

}