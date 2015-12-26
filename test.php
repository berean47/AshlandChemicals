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
$app=new iSDK();
$app->cfgCon($appname,$appkey);

$con=new Infusionsoft_Contact();
$con->load(52549);
var_dump($con);