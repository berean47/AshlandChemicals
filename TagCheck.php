<?php

/**
 * TagChecks only purpose is to check if a tag with the name NewEKM exists and if not create it
 * The other process files will handle finding it...this is just a pre-check to running them
 */

// Dependancies
include_once 'EKMConfig.php';
require_once 'Infusionsoft/infusionsoft.php';
require_once 'src/isdk.php';
// Class objects
$grp=new Infusionsoft_ContactGroup();
$app=new iSDK();
// config IS app connection
$app->cfgCon($appname,$appkey);
// init globals
$tName='ContactGroup';
$limit=1000;
$page=0;
$query=array('Id'=>'%');
$rFields=array('GroupName');
$found=false;
// how many rows in ContactGroup table
$count=$app->dsCount($tName,$query);
// iterate all rows of table
while ($count>0){
    $data=$app->dsQuery($tName,$limit,$page,$query,$rFields);
    foreach ($data as $dat){
        if ($dat['GroupName']=='NewEKM'){
            $found=true;
        }
    }
    $count=$count-1000;
}
// if not found then re-create it
if (!$found){
    $grp->GroupName='NewEKM';
    $grp->GroupDescription='Created by api call for New EKM contacts';
    $grp->save();
}


