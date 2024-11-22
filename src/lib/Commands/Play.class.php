<?php

namespace Commands;

class Play extends Command {

	public function __construct($database) {
		parent::__construct($database, 'play');
	}

	/**
	 * setCommandDefinitions: Sets the command definitions used to install the command
	 *
	 * @return array
	 */
	public function setCommandDefinitions(): array {
		return array(
			'name' => 'play',
			'description' => 'Plays a song!',
			'options' => array(
				'song' => array(
					'description' => 'The song to play',
					'required' => 1
				),
			),
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