<?php

namespace Katu\Utils;

use \Katu\App;
use \Katu\Config;
use \Katu\Types\TUrl;

class Url {

	static function isHttps() {
		return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on';
	}

	static function getCurrent() {
		return new TUrl('http' . (self::isHttps() ? 's' : NULL) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
	}

	static function getBase() {
		return new TUrl(Config::getApp('baseUrl'));
	}

	static function getSite($uri) {
		return new TUrl(self::joinPaths(self::getBase(), $uri));
	}

	static function getFor($handle, $args = array(), $params = array()) {
		$app = App::get();

		return TUrl::make(self::joinPaths(self::getBase()->getHostWithScheme(), $app->urlFor($handle, array_map('urlencode', $args))), $params);
	}

	static function getReturnUrl($defaultRoute, $defaultParams = array()) {
		$app = App::get();

		return $app->request->params('returnUri') ? static::getSite($app->request->params('returnUri')) : \Katu\Utils\Url::getFor($defaultRoute, $defaultParams);
	}

	static function joinPaths() {
		return implode('/', array_map(function($i){
			return trim($i, '/');
		}, func_get_args()));
	}

}
