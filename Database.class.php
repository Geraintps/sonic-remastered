<?php

// Create a database class
class Database {

	private static $instance;

	/**
	 * pool: The pool of connections
	 *
	 * @var array
	 */
	private $pool;

	private function __construct() {
		$this->pool = [];
	}

	/**
	 * runQuery: Run a query on the database
	 *
	 * @param  string $query
	 * @return mixed
	 */
	public function runQuery(string $query) {

		// get a connection
		$connection = $this->getConnection();

		// validate the connection
		if ($connection->connect_error) {

			// set the response
			$response = array(
				'success' => 0,
				'message' => 'Connection failed'
			);

			// log the error
			logError("System", "MySQL Connection Error", __FILE__, __LINE__, __CLASS__, __FUNCTION__, print_r($connection->connect_error, TRUE));

			// return the response
			return $response;
		}

		// check the query
		if ($query == '') {

			// set the response
			$response = array(
				'success' => 0,
				'message' => 'No query provided'
			);

			// return the response
			return $response;
		}

		// check for a debug flag
		if (DEBUG) {
			logError("MySQL", '', __FILE__, __LINE__, __CLASS__, __FUNCTION__, print_r($query, TRUE));
		}

		// run the query
		$result = $connection->query($query);

		// release the connection
		$this->releaseConnection($connection);

		// return the result
		return $result;
	}

	/**
	 * getInstance: Get the instance of the database
	 *
	 * @return Database
	 */
	public static function getInstance() {
		if (!self::$instance) {
			self::$instance = new Database();
		}
		return self::$instance;
	}

	/**
	 * getConnection: Get a connection from the pool
	 *
	 * @return mysqli
	 */
	public function getConnection() {
		if (count($this->pool) > 0) {
			return array_pop($this->pool);
		} else {
			return new mysqli(SERVER_NAME, USERNAME, PASSWORD, DB_NAME);
		}
	}

	/**
	 * releaseConnection: Release a connection back to the pool
	 *
	 * @param mysqli $connection
	 * @return void
	 */
	public function releaseConnection($connection): void {
		array_push($this->pool, $connection);
	}
}
