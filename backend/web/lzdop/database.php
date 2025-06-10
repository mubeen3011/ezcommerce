<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 1/3/2018
 * Time: 5:18 PM
 */


$servername = 'aws-ao-shop2.cpqivss7uidb.us-west-2.rds.amazonaws.com';
$username = 'developer';
$password = 'developer32!';


// Create connection
$conn = mysqli_connect($servername, $username, $password) or die($conn);

// Check connection
if (!$conn) {
    die("Connection failed");
}