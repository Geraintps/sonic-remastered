<?php

namespace Commands;

class Queue extends Command {

	public function __construct($database) {
		parent::__construct($database, 'queue');
	}

	/**
	 * setCommandDefinitions: Sets the command definitions used to install the command
	 *
	 * @return array
	 */
	public function setCommandDefinitions(): array {
		return array(
			'name' => 'queue',
			'description' => 'View the current queue',
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