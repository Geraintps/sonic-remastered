<?php

namespace Commands;

class Command {

	/**
	 * database: The database connection
	 *
	 * @var Database
	 */
	protected $database = null;

	/**
	 * command: The command to execute
	 *
	 * @var string
	 */
	protected $command = '';

	public function __construct($database, $command) {

		// set the database connect
		$this->database = $database;

		// set the command
		$this->command = $command;
	}

	/**
	 * getCommand: Returns the command name
	 *
	 * @return string
	 */
	public function getCommand(): string {
		return $this->command;
	}

	/**
	 * setError: Set an error message
	 *
	 * @param  string $type: The type of error
	 * @param  string $error: The error message
	 * @return array
	 */
	public function setError(string $type, string $error): void {
		logError("Command", $type, __FILE__, __LINE__, __CLASS__, __FUNCTION__, print_r($error, TRUE));
	}
}

?>