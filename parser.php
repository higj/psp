<?php

include 'lib/event.php';
include 'lib/table.php';

$simulation_output = file_get_contents(SIM_OUTPUT_FILE_PATH, FALSE); // Load the simulation output

$events = Event::getEvents($simulation_output); // Fetch all the events

$table = new Table(['Run' , 'φ', 'Δφ', 'κ1', 'Δκ1', 'κ2', 'Δκ2', 'x', 'Δx', 'y', 'Δy', 'z', 'Δz'], 'A10'); // Prepare the Excel table

$good_events_counter = 0;

foreach($events as $event) {
	if($event->hasFullSpectrometerData()) {
		if(($event->number_of_tracks == 3 && $event->number_of_vertices == 3) || ($event->number_of_tracks == 2 && $event->number_of_vertices == 1)) {
			$max_angle_data = $event->maxAngleSpectrometerData();
			
			$kappa1 = $max_angle_data['tracks'][0]['curvature']['value'];
			$kappa2 = $max_angle_data['tracks'][1]['curvature']['value'];
			
			if(($kappa1 < 0) != ($kappa2 < 0)) {
				$good_events_counter++;
				
				$table->addTableRow([
						$good_events_counter, // Run
						$max_angle_data['angle']['value'], // Phi
						$max_angle_data['angle']['error'], // Phi error
						$kappa1, // Kappa1
						$max_angle_data['tracks'][0]['curvature']['error'], // Kappa1 Error
						$kappa2, // Kappa2
						$max_angle_data['tracks'][1]['curvature']['error'], // Kappa2 Error
						$max_angle_data['vertex_coord']['x']['value'],
						$max_angle_data['vertex_coord']['x']['error'],
						$max_angle_data['vertex_coord']['y']['value'],
						$max_angle_data['vertex_coord']['y']['error'],
						$max_angle_data['vertex_coord']['z']['value'],
						$max_angle_data['vertex_coord']['z']['error']
				]);
			}
			
		}
	}
}

// Now, create the header for the Excel table with all the relevant information (Optional)
$table->createHeader('Lambda', 'A1:H', [
		'Momentum' => 8,
		'Total number of injections' => 1000,
		'Number of times Pi-0 decayed into 2 photons' => $good_events_counter,
		'Branching Ratio' => $good_events_counter / 1000
]);

// Save the table
$table->saveTable();