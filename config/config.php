<?php
$username = "PROJECT_LUCKY";
$password = "admin1234";
$connection_string = "localhost"; 

$conn = oci_connect($username, $password, $connection_string, 'AL32UTF8');

if (!$conn) {
    $e = oci_error();
    die(json_encode(['status' => 'error', 'message' => $e['message']]));
}
?>