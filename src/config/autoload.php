<?php
function autoloadClasses($className) {
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    $fileName = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . $className . '.class.php';

	// check if the file exists
	if (file_exists($fileName)) {
        require_once $fileName;
    } else {

        // log the error
		logError("System", "", __FILE__, __LINE__, __CLASS__, __FUNCTION__, print_r("Class file not found: " . $fileName, TRUE));
		logError("System", "BACKTRACE", __FILE__, __LINE__, __CLASS__, __FUNCTION__, print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), TRUE));
	}
}

spl_autoload_register('autoloadClasses');
?>