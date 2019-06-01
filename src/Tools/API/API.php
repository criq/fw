<?php

namespace Katu\Utils\API;

class API {

	static function success($res = null, $options = []) {
		$app = App::get();
		$app->response->setStatus(200);

		return Utils\JSON::respond($res, $options);
	}

	static function error($error = null, $code = null) {
		$errors = [];

		if ($error instanceof \Katu\Exceptions\ExceptionCollection) {
			foreach ($error as $_error) {
				if ($_error instanceof \Katu\Exceptions\Exception) {
					$errors[] = (string) $_error->getTranslatedMessage();
				} else {
					$errors[] = $_error->getMessage();
				}
			}
			$error = $errors;
		} elseif ($error instanceof \Exception) {
			if ($error instanceof \Katu\Exceptions\Exception) {
				$errors[] = (string) $error->getTranslatedMessage();
			} else {
				$errors[] = $error->getMessage();
			}
		} else {
			$errors[] = $error;
		}

		$res = [
			'error' => [
				'message' => $errors,
			],
		];

		if ($code) {
			$res['error']['code'] = $code;
		}

		return static::errors($res);
	}

	static function errors($res = []) {
		$app = App::get();
		$app->response->setStatus(400);

		return Utils\JSON::respond($res);
	}

	static function geTURL($endpoint) {
		return Utils\Url::joinPaths(Config::get('app', 'apiUrl'), $endpoint);
	}

	static function get($endpoint, $params = [], &$curl = null) {
		return \Amiko\Amiko::get(static::geTURL($endpoint), $params, $curl);
	}

	static function post($endpoint, $params = [], &$curl = null) {
		return \Amiko\Amiko::post(static::geTURL($endpoint), $params, $curl);
	}

}
