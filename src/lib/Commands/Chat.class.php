<?php

namespace Commands;

class Chat extends Command {

	public function __construct($database) {
		parent::__construct($database, 'chat');
	}

	/**
	 * setCommandDefinitions: Sets the command definitions used to install the command
	 *
	 * @return array
	 */
	public function setCommandDefinitions(): array {
		return array(
			'name' => 'chat',
			'description' => 'Talk with an AI Language Model',
			'options' => array(
				'message' => array(
					'description' => 'The message to send',
					'required' => 1
				),
				'language' => array(
					'description' => 'The language to use',
					'required' => 0, 
					'remove' => 1
				)
			)
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