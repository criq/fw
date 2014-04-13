<?php

namespace Katu;

class API {

	static function success($res = NULL) {
		$app = App::get();

		$app->response->setStatus(200);
		$app->response->headers->set('Content-Type', 'application/json; charset=UTF-8');
		$app->response->setBody(Utils\JSON::encode($res));

		return TRUE;
	}

	static function error($error = NULL) {
		$app = App::get();

		$app->response->setStatus(400);
		$app->response->headers->set('Content-Type', 'application/json; charset=UTF-8');
		$app->response->setBody(Utils\JSON::encode(array(
			'error' => array(
				'message' => $error,
			),
		)));

		return TRUE;
	}

	static function getURL($endpoint, $params) {
		return Utils\URL::joinPaths(Config::getApp('api_url'), $endpoint) . '?' . http_build_query($params);
	}

	static function useMethod($method, $endpoint, $params = array(), &$curl = NULL) {
		$curl = new \Curl();
		$curl->$method(Utils\URL::joinPaths(Config::getApp('api_url'), $endpoint), $params);

		if ($curl->http_status_code == 200) {
			return Utils\JSON::decodeAsArray($curl->response);
		}

		$array = Utils\JSON::decodeAsArray($curl->response);
		if (isset($array['error']['message'])) {
			throw new Exception($array['error']['message']);
		} else {
			throw new Exception($curl->error_message, $curl->error_code);
		}

		return FALSE;
	}

	static function get($endpoint, $params = array(), &$curl = NULL) {
		return self::useMethod('get', $endpoint, $params, $curl);
	}

	static function post($endpoint, $params = array(), &$curl = NULL) {
		return self::useMethod('post', $endpoint, $params, $curl);
	}

}
