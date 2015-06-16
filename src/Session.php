<?php

namespace Katu;

class Session {

	const KEY = 'katu.session';

	static function start() {
		if (!session_id()) {
			session_start();
		}
	}

	static function init() {
		if (!session_id()) {
			static::setCookieParams();
			session_start();
		}

		if (!isset($_SESSION[static::KEY])) {
			$_SESSION[static::KEY] = array();
		}

		return true;
	}

	static function get($key = null) {
		static::init();

		if (!$key) {
			return $_SESSION[static::KEY];
		}

		if (!isset($_SESSION[static::KEY][$key])) {
			return null;
		}

		return $_SESSION[static::KEY][$key];
	}

	static function set($key, $value) {
		static::init();

		$_SESSION[static::KEY][$key] = $value;

		return true;
	}

	static function add($key, $value, $instance = null) {
		static::init();

		if ($value) {
			if (!is_null($instance)) {
				$_SESSION[static::KEY][$key][$instance] = $value;
			} else {
				$_SESSION[static::KEY][$key][] = $value;
			}
		}

		return true;
	}

	static function reset() {
		static::init();

		if (func_get_args()) {
			foreach (func_get_args() as $key) {
				static::set($key, null);
			}
		} else {
			$_SESSION[static::KEY] = null;
		}

		return true;
	}

	static function setCookieParams($config = array()) {
		try {
			$config = \Katu\Config::getApp('cookie');
		} catch (\Exception $e) {
			$config = array();
		}

		$config = array_merge(Cookie::getDefaultConfig(), $config);

		return session_set_cookie_params($config['lifetime'], $config['path'], $config['domain'], $config['secure'], $config['httponly']);
	}

}
