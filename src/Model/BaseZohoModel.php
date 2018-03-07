<?php
/**
 * Created by PhpStorm.
 * User: grodas.p35
 * Date: 12/25/2017
 * Time: 13:10
 */

namespace assist\model;


use assist\Model;
use GuzzleHttp\Client;

/**
 * Class BaseZohoModel
 * @package assist\model
 * @property  string $api_url
 * @property  string $client (need to send client as parameter on getInstance)
 */
class BaseZohoModel extends Model {
	public $authtoken;


	protected function buildApiLink(string $format, string $module, string $method) {
		return "{$this->api_url}/{$format}/{$module}/{$method}";
	}

	/**
	 * @param string $format
	 * @param string $module
	 * @param string $method
	 * @param bool $add_auth_token
	 * @param array $get_parameters
	 * @return string
	 */
	protected function getMethodLink(string $format, string $module, string $method, $add_auth_token = false, array $get_parameters = []) {
		$link = $this->buildApiLink($format, $module, $method);

		if ($add_auth_token) {
			$get_parameters['authtoken'] = $this->authtoken;
		}

		if (!empty($get_parameters)) {
			$get_parameters += $this->default_parameter;
			$link .= "?" . http_build_query($get_parameters);
		}

		return $link;
	}

	protected function doGetRequest($url, $request_parameters = []) {
		$request = function () use ($url, $request_parameters) {
			if (!empty($request_parameters)) {
				$response = $this->client->get($url, $request_parameters)->getBody()->getContents();
			} else {
				$response = $this->client->get($url)->getBody()->getContents();
			}
			return $response;
		};
		return call_user_func($request);
	}

	protected function doPostRequest($url, $data = [], $request_parameters = []) {
		$options = [];

		if ($data) {
			$options['form_params'] = $data;
		}
		if (!empty($request_parameters)) {
			$options + $request_parameters;
		}
		$response = $this->client->post($url, $options)->getBody()->getContents();
		return $response;
	}
}