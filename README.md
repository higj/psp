# Particle Simulation Parser (PSP)

*PSP* organizes the simulation data for part II of the [Elementary Particles Experiment (TAU)](https://m.tau.ac.il/~lab3/PARTICLES/particles.html). This is an extension of the [ParticleSimulation](https://github.com/elaav/ParticleSimulation) (Python) project by @[elaav](https://github.com/elaav). *PSP* processes the output of injects.py and organizes the events into an array in a logical manner.

## Getting Started

Although *PSP* only needs the simulation output file it is recommended to use it in conjunction with the *ParticleSimulation* script (which should be installed on the TAU server).

### Prerequisites

* [XAMPP](https://www.apachefriends.org/index.html) or any other Apache Distribution in order to execute the PHP scripts on your computer

* (**Optional**) [PhpSpreadsheet](https://phpspreadsheet.readthedocs.io/en/latest/#installation) library for producing Excel spreadsheet

## Configuring the script

If you chose to install the PhpSpreadsheet library you must specify the Composer directory in `config.php`. It usually looks like `C:/Users/<USER_NAME>/vendor/autoload.php` in Windows.

## Using the script

By default, the simulation output (`.txt` file) must be placed in the root directory (i.e. the same directory as `parser.php`), but you can change this setting (`SIM_OUTPUT_FILE_PATH`) in `config.php`. 

The method `Event::getEvents()` (located in `lib/event.php`) constitutes the core of the parser. It scans the events from the output text file and organizes them in an array of `Event` objects. Each such object has the following structure:

<details> 
	<summary>Code Snippet</summary>
	

	Event Object
	(
		[injection_momentum] => 8.0 // Value of the initial momentum (in this example 8.0)
		[spectrometer_data] => Array // Stores the spectrometer data (empty if there's no data)
			(
				[track_data] => Array
					(
						[1] => Array // The index corresponds to the track number (here it means Track No. 1)
							(
								[curvature] => Array
									(
										[value] => -0.130972825E-02
										[error] => 0.10885E-09
									)

								[tandip] => Array
									(
										[value] => 0.099900089
										[error] => 0.22033E-03
									)

							)

						[2] => Array
							(
								[curvature] => Array
									(
										[value] => ...
										[error] => ...
									)

								[tandip] => Array
									(
										[value] => ...
										[error] => ...
									)

							)
				.
				.
				.
					)

				[vertices_data] => Array
					(
						[0] => Array
							(
								[tracks] => Array // Always contains 2 elements - the two tracks corresponding to this vertex
									(
										[0] => 1
										[1] => 2
									)

								[data] => Array
									(
										[coordinates] => Array
											(
												[x] => Array
													(
														[value] => 81.17825
														[error] => 24.59993
													)

												[y] => Array
													(
														[value] => -6.81394
														[error] => 4.69129
													)

												[z] => Array
													(
														[value] => 8.87917
														[error] => 1.97983
													)

											)

										[angle] => Array
											(
												[value] => 0.00072
												[error] => 0.09287
											)

									)

							)
				.
				.
				.
					)

			)

		[cluster_number] => 1 // The number of clusters in the calorimeter
		[cluster_data] => Array // Array with cluster data
			(
				[0] => Array
					(
						[pulse_height] => 23.0
						[x] => 133.0
						[y] => -23.0
						[z] => 16.0
					)

			)

		[has_charged_products] => Boolean // TRUE if charged particles were produced, FALSE otherwise
		[has_muon_hits] => Boolean // Self-explanatory
		[number_of_tracks] => 3 // Self-explanatory
		[number_of_vertices] => 3 // Self-explanatory
	)

</details>

We perform the loading and the filtering of events in `parser.php`. In essence, that's the only file you need to work with.

**Important note**: if you prefer `.txt` output over `.xlsx` use `plain_parser.php` instead (no need to install PhpSpreadsheet in this case)

Results are saved to `results/parsed_output(.xlsx/.txt)` by default, but you can modify the path and the file name in `config.php`.

## Classes & Methods

### Tables (.xlsx)

* `$table = new Table(<table_column_labels>, <table_position>)` creates a new `Table` object and loads it to the variable $table. The first argument to the object constructor is `<table_column_labels>` - an array of strings containing the column labels (e.g. `['First column', 'Second column', ...]`). Second argument is `table_position` - a string which has the format of an Excel spreadsheet cell and specifies where the table should be put (by default it is set to `A1`, i.e. the first column label will be placed there)

* `$table->addTableRow(<value>)` adds a row to the previous `$table`. The argument `<value>` is an array which should have the same order as the column labels. In other words, `<value>` should have the following form `['First cell value', `Second cell value`, ...]`

* `$table->createHeader(<title>, <range>, <data>)` creates a header with specified title (`<title>`) and data provided by an array (`<data>`). Range is a string of the form `'J2:Q'` (default value) and it specifies where the header should be placed and how wide it should be (the height is adjusted automatically, which is why `Q` in this example is not followed by a number). The last argument is `<data>` - an array of the format `['Property' => ['value' => <some_value>, 'unit' => <some_unit>], 'Unitless Property' => <some_value>, ...]`
