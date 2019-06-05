<?php

namespace Katu\Tools\Routing;

class URL {

	static function isHttps() {
		return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on';
	}

	static function getCurrent() {
		return new \Katu\Types\TURL('http' . (static::isHttps() ? 's' : null) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
	}

	static function getBase() {
		return new \Katu\Types\TURL(\Katu\Config\Config::get('app', 'baseUrl'));
	}

	static function getFor($route, $args = [], $params = []) {
		$app = \Katu\App::get();
		$path = $app->getContainer()->get('router')->pathFor($route, array_map('urlencode', (array)$args));

		return \Katu\Types\TURL::make(static::joinPaths(static::getBase()->getHostWithScheme(), $path), $params);
	}

	static function getDecodedFor($route, $args = [], $params = []) {
		$app = \Katu\App::get();
		$path = $app->getContainer()->get('router')->pathFor($route, $args);

		return \Katu\Types\TURL::make(static::joinPaths(static::getBase()->getHostWithScheme(), $path), $params);
	}

	static function joinPaths() {
		return implode('/', array_map(function($i) {
			return trim($i, '/');
		}, func_get_args()));
	}

}
