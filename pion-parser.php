<?php

include 'lib/event.php';
include 'lib/table.php';

$simulation_output = file_get_contents(SIM_OUTPUT_FILE_PATH, FALSE); // Load the simulation output

$events = Event::getEvents($simulation_output); // Fetch all the events (creates an array of Event objects)

// Pion Example

// Prepare the Excel table
$table = new Table(['Run' , 'PH1', 'x1', 'y1', 'z1', 'PH2', 'x2', 'y2', 'z2'], 'A10');

$number_of_injections = 1000; // Change the value if the number of injections was different
$good_events_counter = 0;

// Iterate the events
foreach($events as $event) {
	// We don't want events with charged products (the pion mostly decays into 2 photons)
	// We also want events with only two clusters (corresponding to the two photon hits)
	if(!$event->has_charged_products && $event->cluster_number == 2) {
		$good_events_counter++; // This is a good event
		
		// Add the relevant row in the table (notice that the values are in the same order as the columns from $table definition)
		$table->addTableRow([
				$good_events_counter, // Run
				$event->cluster_data[0]['pulse_height'], // PH1, i.e. the first (index=0) pulse height
				$event->cluster_data[0]['x'], // x1
				$event->cluster_data[0]['y'], // y1
				$event->cluster_data[0]['z'], // z1
				$event->cluster_data[1]['pulse_height'], // PH2, i.e. the second (index=1) pulse height
				$event->cluster_data[1]['x'], // x2
				$event->cluster_data[1]['y'], // y2
				$event->cluster_data[1]['z'], // z2
		]);
	}
}

$table->createHeader('Pion', 'A1:H', [
		'Momentum' => ['value' => 5, 'unit' => 'GeV'], // Edit the values if you injected with a different momentum
		'Total number of injections' => $number_of_injections, 
		'Number of decays into 2 photons' => $good_events_counter, 
		'Branching Ratio' => $good_events_counter / $number_of_injections
]);

// Save the table
$table->saveTable('results/pion_result');