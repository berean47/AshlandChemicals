<?php
/*
 * Create action tag database connection and initialize variables
 */
// $dbhost="localhost";
//Replace the below connection parameters to fit your environment
$dbms="mysql";
$hostip = '173.220.27.246:1433'; 
$dbname = 'SA';
$userid = 'Infusionsoft';
$userpass = '!nfusionS0ft';
$netconn = "$dbms:host=$hostip;dbname=$dbname";
error_reporting(E_ALL);
echo $netconn.'<br>';
try {
//            $conn = new PDO("mysql:host=$host;dbname=$dbname",
//                            $username, $password,true);
			$conn=new PDO($netconn,$userid,$userpass);
			var_dump($conn);
            // execute the stored procedure
            $sql = 'Exec sma_sp_GetListOfCasesFromOpenDate  @FromDate="2015-07-28 20:00:00", @ToDate="2015-07-29 20:00:00";';
            echo $sql.'<br>';
            $q = $conn->query($sql);
            $q->setFetchMode(PDO::FETCH_ASSOC);
        } catch (PDOException $pe) {
            die("Error occurred!!!!!!!!!:" . $pe->getMessage());
            var_dump($conn);
}

var_dump($q);