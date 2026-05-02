<?php
$files = glob('storage/framework/views/*.php');
$found = false;
foreach ($files as $file) {
    exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $returnVar);
    if ($returnVar !== 0) {
        $found = true;
        echo "Error in $file:\n" . implode("\n", $output) . "\n\n";
    }
}
if (!$found) echo "No syntax errors found.\n";
