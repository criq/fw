<?php

namespace Katu;

use \Katu\Utils\Cache;

class Config {

	static function get() {
		$args = func_get_args();

		return Cache::getRuntime(array_merge(['config'], $args), function() use($args) {
			try {
				return call_user_func_array([new \Katu\Types\TArray(self::getAll()), 'getValueByArgs'], $args);
			} catch (\Katu\Exceptions\MissingArrayKeyException $e) {
				throw new \Katu\Exceptions\MissingConfigException("Missing config for " . implode('.', $args) . ".");
			}
		});
	}

	static function getAll() {
		return Cache::getRuntime('config', function() {

			$config = [];
			foreach (self::getFiles() as $file) {
				$pathinfo = pathinfo($file);
				if ($pathinfo['extension'] == 'yaml') {
					$config[$pathinfo['filename']] = \Katu\Utils\YAML::decode($file);
				} else {
					$config[$pathinfo['filename']] = include $file;
				}
			}

			return $config;

		});
	}

	static function getFiles() {
		$dir = BASE_DIR . '/app/Config';
		$files = [];

		foreach (scandir($dir) as $file) {
			if (preg_match('#^[a-z]+\.(php|yaml)$#i', $file)) {
				$files[] = Utils\FileSystem::joinPaths($dir, $file);
			}
		}

		return $files;
	}

	static function getApp() {
		return call_user_func_array(['self', 'get'], array_merge(['app'], func_get_args()));
	}

	static function getDb() {
		return call_user_func_array(['self', 'get'], array_merge(['db'], func_get_args()));
	}

}
