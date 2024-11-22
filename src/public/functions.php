<?php

/**
 * logError: Log an error to a file
 *
 * @param  mixed $type
 * @param  mixed $e
 * @param  mixed $phpFile
 * @param  mixed $line
 * @param  mixed $class
 * @param  mixed $function
 * @param  mixed $details
 * @return void
 */
function logError($type, $e, $phpFile, $line, $class, $function, $details = "") {

	// let's set a unique id if we don't already have one, so we can track session logs
	if (!isset($_SESSION['log-id'])) {
		$_SESSION['log-id'] = uniqid('id_', TRUE);
	}

	// get the date and log
	$d = new DateTime("now", new DateTimeZone("UTC"));
	$filename = getLogFile($type, $e, $d);
	if ($filename !== false) {
		$file = fopen($filename, "a+");
		$str = "--STARTING LOG--\n".$line.": ".$phpFile."\n";
		if ($class != "") {
			$str .= "Class: ".$class."\n";
		}
		if ($function != "") {
			$str .= "Function: ".$function."\n";
		}
		if (isset($_SESSION['user_id']) && isset($_SESSION['user_fullname'])) {
			$str .= "User: ".$_SESSION['user_fullname']." (".$_SESSION['user_id'].")\n";
		}
		$str .= "LOG ID: " . $_SESSION['log-id'] . "\n";
		fwrite($file, $str);
		if (is_array($e)) {
			$e = implode("\n", $e);
		}
		fwrite($file, $d->format("Y-m-d H:i:s").": ".$e."\n");

		// check if this is a mysql log and add details if so
		if (strtolower($type) == "mysql") {
			if (isset($_SERVER)) {
				$details .= "\nSERVER: ".print_r($_SERVER, TRUE);
			} else {
				$details .= "\nNO SERVER?";
			}
			$details .= "\nDEBUG: ".print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), TRUE);
		}

		if ($details != "") {
			fwrite($file, "Details:\n");
			fwrite($file, $details);
		}
		fwrite($file, "\n\n");
		// close the file
		fclose($file);
		return true;
	}
	return false;
}

/**
 * getLogFile: Get the log file path
 *
 * @param  mixed $filename
 * @param  mixed $error
 * @param  mixed $d
 * @return string
 */
function getLogFile($filename, $error, $d = ""): string {

	$d = ($d == "") ? new DateTime("now") : $d;
	$date = $d->format("Ymd");
	$filepath = "../logs/";

	// if we have certain log files, we might put them in subfolders - we want big errors in the main folder and info in sub-folders
	// TODO: Create a more robust error process
	$error = trim($error);
	if (strtolower($error) != "error") {
		if (($filename == "CRON" and $error == "Success")
			or ($filename == "MySQL"
				and (
					stripos($error, "Unknown Error") !== 0
					and $error != "Missing DB Data details"
					and $error != "No instance?"
				)
			)
			or (stripos($filename, "API") === 0 and stripos($filename, "API-Speed") !== 0)
			or ($filename == "Jobs" and stripos($error, "ERROR") !== 0)
			or ($filename == "Dante")
		) {
			$dash_pos = strpos($filename, "-");
			$file_folder = ($dash_pos !== FALSE) ? substr($filename, 0, $dash_pos) : $filename;
			$filepath .= date('Ym') . '-' . $file_folder . "/";
		}
	}

	if (!file_exists($filepath)) {
		mkdir($filepath, FOLDER_PERMISSION, true);
	}

	// work out the environment
	if (defined('ENVIRONMENT')) {
		$environment = ENVIRONMENT;
	} else {
		$environment = "#";
	}

	if(!empty($environment)) {
		$filename = $environment . "-" . $filename;
	}
	$filename = $date."-".$filename.".log";

	if (!file_exists($filepath.$filename)) {
		$file = fopen($filepath.$filename, "w");
		if (!$file) {
			return false;
		} else {
			// wipe the file
			ftruncate($file, 0);
			// close the file
			fclose($file);
		}
	}
	return $filepath.$filename;
}

function updateCommands($database): void {

	// check if we have a database connection
	if($database instanceof Database) {

		// get the commands directory
		$directory = __DIR__ . '/Commands';
		$files = glob($directory . '/*.php');

		foreach ($files as $file) {
			$filename = basename($file);
			$currentHash = md5_file($file);

			// get the class name
			$className = pathinfo($filename, PATHINFO_FILENAME);

			// remove .class from the name
			$className = str_replace('.class', '', $className);

			// check this isn't the main Command class
			if ($className != 'Command') {

				// set the changed flag
				$changed = false;

				// retrieve stored hash
				$query = "SELECT moha_id, moha_filename, moha_hash FROM module_hashes WHERE moha_filename = '" . $filename . "' AND moha_deleted IS NULL LIMIT 0, 1";
				$result = $database->runQuery($query);
				if ($result->num_rows == 1) {
					$row = $result->fetch_assoc();
					$storedHash = $row['moha_hash'];
					$id = $row['moha_id'];

					// check if the hashes are different
					if ($currentHash != $storedHash) {

						// set the changed flag
						$changed = true;

						// update existing record
						$query = "UPDATE module_hashes SET moha_hash = '" . $currentHash . "' WHERE moha_id = '" . $id . "'";
						$database->runQuery($query);
					}
				} else if($result->num_rows == 0) {

					// set the changed flag
					$changed = true;

					// insert new record
					$query = "INSERT INTO module_hashes (moha_filename, moha_hash) VALUES ('" . $filename . "', '" . $currentHash . "')";
					$database->runQuery($query);
				}

				// check if we need to update the command definitions
				if($changed) {

					// get the file and check if the class exists
					require_once $file;
					$class_name = 'Commands\\' . $className;
					if (class_exists($class_name)) {
						$reflection = new ReflectionClass($class_name);
						if ($reflection->hasMethod('setCommandDefinitions')) {

							// get the module
							$Module = new $class_name($database);
							$definitions = $Module->setCommandDefinitions();

							// check if we have definitions
							if (is_array($definitions)) {

								// check if we have a command name
								if (isset($definitions['name'])
									and $definitions['name'] != ''
								) {

									// get the name
									$name = (isset($definitions['name'])) ? $definitions['name'] : '';

									// check if we have a description
									$description = (isset($definitions['description'])) ? $definitions['description'] : '';

									// check if we have a module
									$module = (isset($definitions['module'])) ? $definitions['module'] : '';

									// set the command id
									$commandId = 0;

									// check if we have a command id
									$query = "SELECT comm_id FROM commands WHERE comm_name = '" . $name . "' AND comm_deleted IS NULL LIMIT 0, 1";
									$result = $database->runQuery($query);
									if ($result->num_rows == 1) {
										$row = $result->fetch_assoc();
										$commandId = $row['comm_id'];

										// update the command name and description
										$query = "UPDATE commands SET comm_name = '" . $name . "', comm_description = '" . $description . "', comm_module = '" . $module . "' WHERE comm_id = '" . $commandId . "'";
										$database->runQuery($query);
									} else if($result->num_rows == 0) {

										// insert the command
										$query = "INSERT INTO commands (comm_name, comm_description, comm_module) VALUES ('" . $name . "', '" . $description . "', '" . $module . "')";
										$database->runQuery($query);

										// get the command id
										$query = "SELECT comm_id FROM commands WHERE comm_name = '" . $name . "' AND comm_deleted IS NULL LIMIT 0, 1";
										$result = $database->runQuery($query);
										if ($result->num_rows == 1) {
											$row = $result->fetch_assoc();
											$commandId = $row['comm_id'];
										}
									}

									// check if we have a command id
									if ($commandId > 0) {

										// get the command options
										$query = "SELECT coop_id, coop_commandid, coop_name AS name, coop_description AS description, coop_required AS required FROM command_options WHERE coop_commandid = '" . $commandId . "' AND coop_deleted IS NULL";
										$result = $database->runQuery($query);
										$storedOptions = array();
										if ($result->num_rows > 0) {
											$rows = $result->fetch_all(MYSQLI_ASSOC);
											foreach ($rows as $row) {
												$storedOptions[$row['coop_id']] = $row;
											}
										}

										// loop through the options
										if (is_array($definitions['options'])) {
											foreach ($definitions['options'] as $optionName => $option) {

												// check if we have a stored option
												$storedOption = null;
												foreach ($storedOptions as $storedOpt) {
													if ($storedOpt['name'] == $optionName) {
														$storedOption = $storedOpt;
														break;
													}
												}

												// check if we have a stored option
												if ($storedOption != null) {

													// check if either the name, description or required has changed
													if ($storedOption['name'] != $optionName
														or $storedOption['description'] != $option['description']
														or $storedOption['required'] != $option['required']
													) {

														// check we shouldn't remove the option
														if(!isset($option['remove'])
															or !filter_var($option['remove'], FILTER_VALIDATE_BOOLEAN)
														) {

															// update the option
															$query = "UPDATE command_options SET coop_name = '" . $optionName . "', coop_description = '" . $option['description'] . "', coop_required = '" . $option['required'] . "' WHERE coop_id = '" . $storedOption['coop_id'] . "'";
															$database->runQuery($query);
														}
													}

													// check for a remove flag
													if(isset($option['remove'])
														and filter_var($option['remove'], FILTER_VALIDATE_BOOLEAN)
													) {

														// remove the option
														$query = "UPDATE command_options SET coop_deleted = '1', coop_deleteddate = NOW() WHERE coop_id = '" . $storedOption['coop_id'] . "'";
														$database->runQuery($query);
													}
												} else {

													// insert the option
													$query = "INSERT INTO command_options (coop_commandid, coop_name, coop_description, coop_required) VALUES ('" . $commandId . "', '" . $optionName . "', '" . $option['description'] . "', '" . $option['required'] . "')";
													$database->runQuery($query);
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}
}

/**
 * getSettings: Returns the settings for the client
 *
 * @param Database $database
 * @return array
 */
function getSettings($database): array {

	// check if we have a database connection
	if($database instanceof Database) {

		// update the commands
		updateCommands($database);

		// build the query
		$query = "SELECT * FROM
				sys_settings
			WHERE
				sys_sett_deleted IS NULL";

		// run the query
		$result = $database->runQuery($query);
		if ($result->num_rows > 0) {
			$rows = $result->fetch_all(MYSQLI_ASSOC);

			// set the settings array
			$settings = array();

			// loop through the results
			foreach ($rows as $row) {
				$settings[$row['sys_sett_param']] = $row['sys_sett_value'];
			}

			// get the commands
			$commands = getCommands($database);

			// return the settings
			return array(
				'success' => 1,
				'message' => 'Settings retrieved',
				'settings' => $settings,
				'commands' => $commands
			);
		} else {
			return array(
				'success' => 0,
				'message' => 'No client settings found'
			);
		}
	}

	// return an error
	return array(
		'success' => 0,
		'message' => 'No database connection'
	);
}

function getCommands($database): array {

	// set the commands array
	$commands = array();

	// check if we have a database connection
	if($database instanceof Database) {

		// build the query
		$query = "SELECT
				comm_id,
				comm_name AS name,
				comm_description AS description,
				comm_module AS module
			FROM
				commands
			WHERE
				comm_deleted IS NULL";

		// run the query
		$result = $database->runQuery($query);
		if ($result->num_rows > 0) {
			$rows = $result->fetch_all(MYSQLI_ASSOC);

			// loop through the results
			foreach ($rows as $row) {
				$commands[$row['comm_id']] = $row;
			}

			// get a list of command ids
			$command_ids = array_keys($commands);

			// build the query
			$query = "SELECT
					coop_id,
					coop_commandid,
					coop_name AS name,
					coop_description AS description,
					coop_required AS required
				FROM
					command_options
				WHERE
					coop_commandid IN (".implode(",", $command_ids).")
					AND coop_deleted IS NULL";

			// run the query
			$result = $database->runQuery($query);
			if ($result->num_rows > 0) {
				$rows = $result->fetch_all(MYSQLI_ASSOC);

				// loop through the results
				foreach ($rows as $row) {
					$commands[$row['coop_commandid']]['options'][$row['coop_id']] = $row;
				}
			}
		}
	}
	return $commands;
}

/**
 * decrypt: Decrypts incoming data
 *
 * @param  mixed $encryptedData
 * @param  mixed $iv
 * @param  mixed $key
 * @param  mixed $algorithm
 * @return mixed
 */
function decrypt($encryptedData, $iv, $key, $algorithm) {
    $iv = base64_decode($iv);
    $encryptedData = base64_decode($encryptedData);
    return openssl_decrypt($encryptedData, $algorithm, $key, OPENSSL_RAW_DATA, $iv);
}

/**
 * encrypt: Encrypts data
 *
 * @param  mixed $data
 * @param  mixed $key
 * @param  mixed $iv
 * @return mixed
 */
function encrypt($data, $key, $iv) {
	$encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($encrypted);
}

?>