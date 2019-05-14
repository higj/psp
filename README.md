# Particle Simulation Parser (PSP)

*PSP* organizes the simulation data for part II of the [Elementary Particles Experiment (TAU)](https://m.tau.ac.il/~lab3/PARTICLES/particles.html). This is an extension of the [ParticleSimulation](https://github.com/elaav/ParticleSimulation) (Python) project by @[elaav](https://github.com/elaav). *PSP* processes the output of injects.py and organizes the events into an array in a logical manner.

## Getting Started

Although *PSP* only needs the simulation output file it is recommended to use it in conjunction with the *ParticleSimulation* script (which should be installed on the TAU server).

### Prerequisites

* [XAMPP](https://www.apachefriends.org/index.html) or any other Apache Distribution in order to execute the PHP scripts on your computer

* [PhpSpreadsheet](https://phpspreadsheet.readthedocs.io/en/latest/#installation) library as the script relies on it for producing Excel tables

## Configuring the script

After installing the PhpSpreadsheet library you must specify the Composer directory in `config.php`. It usually looks like `C:/Users/<USER_NAME>/vendor/autoload.php` in Windows.

## Using the script

By default, the simulation output (`.txt` file) must be placed in the root directory (i.e. the same directory as `parser.php`), but you can change this setting (`SIM_OUTPUT_FILE_PATH`) in `config.php`. 

The method `getEvents()` (located in `lib/functions.php`) consitutes the core of the parser. It scans the events from the output text file and organizes them in an array, which has the following structure:

```
[0] => Array // First event (notice the index starts from 0)
	(
		[injection_momentum] =>  8.0 // Value of the initial momentum (e.g. 8)
		[has_charged_products] => Boolean // TRUE if charged particles were produced, FALSE otherwise
		[has_muon_detector_hits] => Boolean // Self-explanatory
		[cluster_number] => 2 // The number of clusters in the calorimeter (e.g. 2)
		[cluster_data] => Array // Array with cluster data (NULL if there are zero clusters)
			(
				[0] => Array // First cluster
					(
						[pulse_height] => 32.0
						[x] => 133.0
						[y] => -17.8
						[z] => 6.0
					)

				[1] => Array // Second cluster
					(
						[pulse_height] => ...
						[x] => ...
						[y] => ...
						[z] => ...
					)
					
				.
				.
				.

			)

		[spectrometer_data] => Array // Stores the spectrometer data (empty if there's no data)
			(
				[track_data] => Array
					(
						[1] => Array // The index corresponds to the track number
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

				[vertices_data] => Array // Contains vertices data (if the reconstruction was successful)
					(
						[0] => Array // First vertex
							(
								[tracks] => Array // This array always contains two elements - the two tracks corresponding to this vertex
									(
										[0] => 1
										[1] => 2
									)

								[data] => Array // This array contains the vertex coordinates as well the the angle
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

						[1] => Array // Second vertex
							(
								[tracks] => Array
									(
										[0] => 1
										[1] => 3
									)

								.
								.
								.

							)

					)

			)

	)
	
[1] => Array // Second event 
	.
	.
	.
```


We perform the loading and the filtering of the events in `parser.php`. In essence, that's the only file you need to work with.

Note that by default results are saved to `results/parsed_output.xlsx`, but you can modify the path and the file name in `config.php`.