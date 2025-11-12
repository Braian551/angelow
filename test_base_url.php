<?php
require_once 'config.php';
echo 'BASE_URL: ' . BASE_URL . PHP_EOL;
echo 'Current working directory: ' . getcwd() . PHP_EOL;
echo 'Document root: ' . $_SERVER['DOCUMENT_ROOT'] . PHP_EOL;
echo 'Script name: ' . $_SERVER['SCRIPT_NAME'] . PHP_EOL;
?>