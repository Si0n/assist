<?php

namespace assist\model;

use GuzzleHttp\Client;

/**
 * Class ZohoComponent
 * @package common\components
 */
class ZohoBooksModel extends BaseZohoModel {

	public function getSalesOrder($order_id) {
		if ($response = $this->doGetRequest($this->getMethodLink("salesorders", $order_id))) {
			return $this->afterAction($response);
		} else {
			return $response;
		}
	}

	public function convertSalesOrderToInvoice($order_id) {
		return $this->doPostRequest($this->getMethodLink("invoices/fromsalesorder", null, true, ['salesorder_id' => $order_id]));
	}

	public function emailInvoices($invoice_ids, $data) {
		return $this->doPostRequest($this->getMethodLink("invoices/email", null, true, ['invoice_ids' => $invoice_ids]), ["JSONString" => json_encode($data)]);
	}

	public function emailInvoice($invoice_id, $get, $data) {
		return $this->doPostRequest($this->getMethodLink("invoices/$invoice_id/email", null, true, $get), ["JSONString" => json_encode($data)]);
	}

	public function applyPaymentToInvoice($invoice_id, $data) {
		return $this->doPostRequest($this->getMethodLink("invoices/$invoice_id/credits", null, true), ["JSONString" => json_encode($data)]);
	}

	public function addCustomerPayment($data) {
		return $this->doPostRequest($this->getMethodLink("customerpayments", null, true), ["JSONString" => json_encode($data)]);
	}

	public function getContactPersons($customer_id) {
		$contact_person = $this->doGetRequest($this->getMethodLink("contacts/$customer_id/contactpersons"));
		return \GuzzleHttp\json_decode($contact_person, true);
	}

	public function createInvoiceFromSalesOrder($salesOrder) {
		$contact_person = $this->getContactPersons($salesOrder['customer_id']);
		$preparedData = [
			"customer_id" => $salesOrder['customer_id'],
			"contact_persons" => !empty($contact_person['contact_persons']) && is_array($contact_person['contact_persons']) ? array_filter(array_map(function ($person) {
				return $person['contact_person_id'];
			}, $contact_person['contact_persons']), function ($i) {
				return !empty($i);
			}) : [],
			"is_discount_before_tax" => $salesOrder['is_discount_before_tax'] ? 'true' : 'false',
			"is_inclusive_tax" => $salesOrder['is_inclusive_tax'] ? 'true' : 'false',
			"salesorder_id" => $salesOrder["salesorder_id"],
			"line_items" => array_map(function ($item) {
				return [
					"item_id" => $item["item_id"],
					"name" => $item["name"],
					"description" => $item["description"],
					"item_order" => $item["item_order"],
					"bcy_rate" => $item["bcy_rate"],
					"rate" => $item["rate"],
					"quantity" => $item["quantity"],
					"unit" => $item["unit"],
					"discount" => $item["discount"],
					"tax_id" => $item["tax_id"],
					"tax_name" => $item["tax_name"],
					"tax_type" => $item["tax_type"],
					"tax_percentage" => $item["tax_percentage"],
					"item_total" => $item["item_total"],
				];
			}, $salesOrder['line_items']),
		];
		foreach (['tax_id', 'adjustment_description', 'adjustment', 'shipping_charge', 'terms', 'notes', 'date', 'discount', 'discount_type', 'exchange_rate', 'salesperson_name'] as $item) {
			if (isset($salesOrder[$item])) {
				$preparedData[$item] = $salesOrder[$item];
			}
		}

		return $this->doPostRequest($this->getMethodLink("invoices", null, true), ["JSONString" => json_encode($preparedData), "send" => "true"]);
	}

	public function createRecord(string $module, array $jsonStringData, array $additionalParameters = []) {
		return $this->doPostRequest($this->getMethodLink($module, null, true), $additionalParameters + ["JSONString" => json_encode($jsonStringData)]);
	}

	/**
	 * @param $data
	 * @param $additional_params //send = 'true'|'false' for email send on invoice creation
	 * @return mixed|string
	 */
	public function createInvoice($data, $additional_params = []) {
		$data = ["JSONString" => json_encode($data)];
		if (!empty($additional_params)) {
			$data += $additional_params;
		}
		return $this->doPostRequest($this->getMethodLink("invoices", null, true), $data);
	}

	public function getOne($module, $id) {
		return $this->doGetRequest($this->getMethodLink($module, $id, true));
	}

	/**
	 * @param $module
	 * @param array $parameters
	 * @param bool $callback
	 * @return array|mixed
	 */
	public function findOne($module, $parameters = [], $callback = false) {
		$list = $this->find($module, $parameters);
		if (!empty($list)) {
			if ($callback && is_callable($callback)) {
				return call_user_func($callback->bindTo($this), current($list));
			} else {
				return current($list);
			}
		}
		return [];
	}

	/**
	 * @param $module
	 * @param array $parameters
	 * @param bool $context
	 * @param bool $callback
	 * @return array|mixed|null
	 */
	public function find($module, $parameters = [], $context = false, $callback = false) {
		if ($response = $this->doGetRequest($this->getMethodLink($module, null, true, $parameters))) {
			$response = \GuzzleHttp\json_decode($response, true);
			if ($response['code'] === 0 && !empty($response[$module])) {
				if ($callback && is_callable($callback)) {
					return call_user_func($callback->bindTo($this), ($context ? $response : $response[$module]));
				} else {
					return ($context ? $response : $response[$module]);
				}
			}
			return [];
		}
		return null;
	}

	public function findAll($module, $parameters = []) {
		$total = [];
		$sub_parameter = ['page' => 1];
		while ($response = $this->find($module, $sub_parameter + $parameters)) {
			$sub_parameter['page']++;
			$total = array_merge($total, $response);
		}
		return $total;
	}

	public function afterAction($output) {
		if ($json = \GuzzleHttp\json_decode($output, true)) {
			return $json;
		} else {
			return $output;
		}
	}

	/**
	 * @param string $module
	 * @param null $id
	 * @param bool $add_authtoken
	 * @param array $get_parameters
	 * @return string
	 */
	protected function getMethodLink(string $module, $id = null, $add_authtoken = false, array $get_parameters = []) {
		$link = $this->buildApiLink($module, $id);
		if ($add_authtoken) {
			$get_parameters['authtoken'] = $this->authtoken;
		}
		if (!empty($get_parameters)) {
			$get_parameters += $this->default_parameter;
			$link .= "?" . http_build_query($get_parameters);
		}
		return $link;
	}

	/**
	 * @param string $module
	 * @param null $id
	 * @return string
	 */
	protected function buildApiLink(string $module, $id = null) {
		if (!is_null($id)) {
			return "{$this->api_url}/{$module}/$id";
		} else {
			return "{$this->api_url}/{$module}";
		}
	}

	/**
	 * @param $url
	 * @return mixed|string
	 */
	protected function doGetRequest($url) {
		$request = function () use ($url) {
			$response = "";
			try {
				$curl = curl_init();

				curl_setopt_array($curl, array(
					CURLOPT_URL => $url,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_ENCODING => "",
					CURLOPT_MAXREDIRS => 10,
					CURLOPT_TIMEOUT => 30,
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST => "GET",
					CURLOPT_HTTPHEADER => array(
						"Authorization: Zoho-authtoken {$this->authtoken}",
						"cache-control: no-cache",
						//"content-type: application/x-www-form-urlencoded"
					),
				));
				$response = curl_exec($curl);
				if (FALSE === $response) {
					throw new Exception(curl_error($curl), curl_errno($curl));
				}
			} catch (Exception $e) {
				trigger_error(sprintf(
					'Curl failed with error #%d: %s',
					$e->getCode(), $e->getMessage()),
					E_USER_ERROR);
			}
			return $response;
		};
		return call_user_func($request);
	}

	public static function unwrapResponse(string $response, $module = false) {
		if (($response = \GuzzleHttp\json_decode($response, true)) && isset($response['code']) && $response['code'] === 0) {
			if ($module && isset($response[$module])) {
				return $response[$module];
			} else {
				return $response;
			}
		}
		return $response;
	}

	protected function doPostRequest($url, $data = []) {
		if ($data) {
			$data = http_build_query($data);
		}
		$response = "";
		try {
			$curl = curl_init();
			curl_setopt_array($curl, array(
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => $data,
				CURLOPT_HTTPHEADER => array(
					//'Authorization' => "Zoho-authtoken {$this->authtoken}",
					"cache-control: no-cache",
					//"content-type: application/x-www-form-urlencoded"
				)
			));
			$response = curl_exec($curl);
			if (FALSE === $response) {
				throw new Exception(curl_error($curl), curl_errno($curl));
			}
		} catch (Exception $e) {
			trigger_error(sprintf(
				'Curl failed with error #%d: %s',
				$e->getCode(), $e->getMessage()),
				E_USER_ERROR);
		}
		return $response;
	}
}