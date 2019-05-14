<?php

define('SIM_OUTPUT_FILE_PATH', './output.txt'); // Simulation output file directory
define('PARSED_OUTPUT_FILE_PATH', './results/parsed_output'); // Name of the processed Excel file

// If you want to have an Excel table as the output, configure the directory of Composer (for PhpSpreadsheet)
define('COMPOSER_AUTOLOAD_DIR', 'C:/Users/<USER_NAME>/vendor/autoload.php');

if(file_exists(COMPOSER_AUTOLOAD_DIR)) {
	require_once COMPOSER_AUTOLOAD_DIR; // Change to your directory
}