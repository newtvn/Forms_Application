<?php

// Test endpoint for debugging
// Save this as test_input.php

ini_set('display_errors', 1);
error_reporting(E_ALL);
header("Content-Type: text/plain; charset=utf-8");

$inputData = file_get_contents('php://input');
echo "Received Data:\n";
echo $inputData;
?>