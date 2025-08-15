<?php
$db = new SQLite3(__DIR__ . '/steg_interns.db'); // __DIR__ is the current directory
if ($db) {
    echo "SQLite DB opened successfully!";
} else {
    echo "Failed to open DB!";
}
?>