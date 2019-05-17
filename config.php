<?php

define('SIM_OUTPUT_FILE_PATH', './output.txt'); // Simulation output file directory
define('PARSED_OUTPUT_FILE_PATH', './results/parsed_output'); // Processed output file directory

$GLOBALS['all_available_particles'] = ['photon', 'electron', 'positron', 'muon', 'mu-plus', 'neutrino', 'pi-0', 'pi-plus', 'pi-minus', 'k-long', 'k-short', 'k-plus', 'k-minus', 'neutron', 'proton', 'antiproton', 'lambda', 'antilambda', 'sigma-plus', 'sigma-minus', 'sigma-0'];

// If you want to have an Excel table as the output, configure the directory of Composer (for PhpSpreadsheet)
define('COMPOSER_AUTOLOAD_DIR', 'C:/Users/<USER_NAME>/vendor/autoload.php');

if(file_exists(COMPOSER_AUTOLOAD_DIR)) {
	require_once COMPOSER_AUTOLOAD_DIR;
}
