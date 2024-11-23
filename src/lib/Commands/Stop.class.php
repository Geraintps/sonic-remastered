<?php

namespace Commands;

class Stop extends Command {

	public function __construct($database) {
		parent::__construct($database, 'stop');
	}

	/**
	 * setCommandDefinitions: Sets the command definitions used to install the command
	 *
	 * @return array
	 */
	public function setCommandDefinitions(): array {
		return array(
			'name' => 'stop',
			'description' => 'Stop the current song and clear the queue',
			'options' => array(),
			'module' => 'Voice'
		);
	}

	/**
	 * execute: Execute the command and return the data
	 *
	 * @param array $data
	 * @return array
	 */
	public function execute($data): array {
		$return = array(
			'success' => 1,
			'message' => 'Hello World',
		);

		return $return;
	}
}
?>