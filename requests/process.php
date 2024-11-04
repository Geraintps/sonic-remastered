<?php

require '../config.php';
require_once '../autoload.php';

// set up the database connection
require_once '../Database.class.php';
$database = Database::getInstance();

// get the encryption key
$key = hex2bin(getenv('ENCRYPTION_KEY'));

// set the default return
$return = array(
	'success' => 0,
);

// get the post data
$postData = json_decode(file_get_contents('php://input'), true);

// check if the post data is empty
if (empty($postData)) {
	$return = array(
		'success' => 0,
		'message' => 'No data was provided'
	);
} else {

	// set the encryption algorithm
	$algorithm = 'aes-256-cbc';

	// get the encrypted data
	$encryptedData = (isset($postData['encryptedData'])) ? $postData['encryptedData'] : '';
	$iv = (isset($postData['iv'])) ? $postData['iv'] : '';

	// check if the encrypted data is set
	if($encryptedData != ''
		and $iv != ''
	) {

		// decrypt the data
		$decryptedData = decrypt($encryptedData, $iv, $key, $algorithm);

		// check for errors
		if($decryptedData === false) {
			$return = array(
				'success' => 0,
				'message' => 'An error occured while decrypting the data'
			);

			// log the error
			logError("PHP", 'Decrypt', __FILE__, __LINE__, __CLASS__, __FUNCTION__, print_r(openssl_error_string(), TRUE));
		} else {

			// decode the decrypted data
			$postData = json_decode($decryptedData, true);

			// check if the action is set
			if (isset($postData['action'])) {

				// get the action
				$action = $postData['action'];

				// check what action is being requested
				switch($action) {
					case 'getSettings':
						$return = getSettings($database);
						break;
					default:

						// get the command
						$class_name = (isset($postData['command'])) ? 'Commands\\' . $postData['command'] : '';

						// check if we have a class name
						if($class_name != ''
							and class_exists($class_name)
						) {

							// try to execute the command
							try {

								// initialise the command class
								$Class = new $class_name($database);

								// execute the command
								$return = $Class->execute($postData);

							} catch (Exception $e) {

								$return = array(
									'success' => 0,
									'message' => 'An error occured while processing the command'
								);

								// log the error
								logError("PHP", '', __FILE__, __LINE__, __CLASS__, __FUNCTION__, print_r($e->getMessage(), TRUE));
							}

						} else {
							$return = array(
								'success' => 0,
								'message' => 'No command was provided'
							);
						}
						break;
				}
			} else {
				$return = array(
					'success' => 0,
					'message' => 'No decrypted data was recieved'
				);
			}
		}
	} else {
		$return = array(
			'success' => 0,
			'message' => 'No encrypted data was provided'
		);
	}
}

// encode the data
$jsonData = json_encode($return);
$iv = openssl_random_pseudo_bytes(16);
$encryptedData = encrypt($jsonData, $key, $iv);

// set the response data
$response = array(
	'encryptedData' => $encryptedData,
	'iv' => base64_encode($iv)
);

// return the data
echo json_encode($response);
exit;
?>