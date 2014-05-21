<?php

namespace Katu;

class ErrorHandler {

	const LOG_DIR   = 'logs';
	const ERROR_LOG = 'error.log';

	static function init() {
		// Constants.
		if (!defined('BASE_DIR')) {
			define('BASE_DIR', realpath(__DIR__ . '/../../../../'));
		}
		if (!defined('LOG_PATH')) {
			define('LOG_PATH', Utils\FS::joinPaths(BASE_DIR, static::LOG_DIR));
		}
		if (!defined('ERROR_LOG')) {
			define('ERROR_LOG', Utils\FS::joinPaths(LOG_PATH, static::ERROR_LOG));
		}

		set_exception_handler(function ($exception) {
			static::log($exception->getMessage());

			return TRUE;
		});

		register_shutdown_function(function() {
			$error = error_get_last();

			static::log($error['message'], $error['type'], $error['file'], $error['line']);

			return static::plainError('An error occured.');
		});

		set_error_handler(function ($message, $level = 0, $file = NULL, $line = NULL) {
			throw new \ErrorException($message, 0, $level, $file, $line);
		});

		return TRUE;
	}

	static function plainError($error) {
		header('Content-Type: text/plain; charset=UTF-8');

		die($error);
	}

	static function log($message, $level = 0, $file = NULL, $line = NULL) {
		if ($message) {
			$log = new \Monolog\Logger('app');
			$log->pushHandler(new \Monolog\Handler\StreamHandler(ERROR_LOG));
			$log->addError($message, array(
				'level' => $level,
				'file'  => $file,
				'line'  => $line,
			));

			return TRUE;
		}

		return FALSE;
	}

}
