<?php

namespace assist\model;

use GuzzleHttp\Client;

/**
 * Class ZohoComponent
 * @package common\components
 */
class ZohoModel extends BaseZohoModel {
	public $default_parameter = [
		'scope' => 'crmapi',
		'newFormat' => 1
	];

	public function insertRecords($module, $xml, $formParams = null) {
		if (empty($xml)) return false;
		if (is_array($xml)) {
			if (!is_array(current($xml))) $xml = [$xml];
			$xml = "<$module>" . $this->convertArrayToXml($xml) . "</$module>";
		}
		if (empty($formParams) && !is_array($formParams)) $formParams = [];
		$formParams["xmlData"] = $xml;
		return $this->client->post($this->getMethodLink('json', $module, 'insertRecords', true), ['form_params' => $formParams])->getBody()->getContents();
	}

	public function updateRecords($module, $xml, $id, $formParams = null) {
		if (empty($xml)) return false;
		if (is_array($xml)) {
			if (!is_array(current($xml))) $xml = [$xml];
			$xml = "<$module>" . $this->convertArrayToXml($xml) . "</$module>";
		}
		if (empty($formParams) && !is_array($formParams)) $formParams = [];
		$formParams["xmlData"] = $xml;
		return $this->client->post($this->getMethodLink('json', $module, 'updateRecords', true, ['id' => $id]), ['form_params' => $formParams])->getBody()->getContents();
	}
	public function getZohoRecordById($module, $record_id, $format = "json") {
		return $this->doGetRequest($this->getMethodLink($format, $module, 'getRecordById', true, ['id' => $record_id]));
	}

	public function getZohoRecords($module, $format = "json") {
		return $this->doGetRequest($this->getMethodLink($format, $module, 'getRecords', true));
	}
	public function searchRecords($module, $criteria, $format = "json") {
		return $this->doGetRequest($this->getMethodLink($format, $module, 'searchRecords', true, ['criteria' => $criteria]));
	}
	public function getRelatedRecords($relatedModule, $parentModule, $parentId, $format = "json") {
		return $this->doGetRequest($this->getMethodLink($format, $relatedModule, 'getRelatedRecords', true, ['id' => $parentId, "parentModule" => $parentModule, "fromIndex" => 1, "toIndex" => 200]));
	}

	/**
	 * @param $json
	 * @param $module
	 * @return array|bool
	 */
	public function processResponse($json, $module) {
		try {
			$response = \GuzzleHttp\json_decode($json, true);
			if (isset($response['response'])) {
				$response = $response['response'];
			}
			if (isset($response['nodata'])) {
				return false;
			} else {
				if (isset($response['result'][$module]['row']['FL'])) {
					return [$response['result'][$module]['row']];
				} elseif (isset($response['result'][$module]['row'])) {
					return $response['result'][$module]['row'];
				} else {
					Throw new Exception('Wrong format answer ZOHO', 500);
				}
			}
			return $response;
		} catch (\Throwable $e) {
			return false;
		}
	}

	public function convertArrayToXml(array $array, $sub_tree = "row") {
		$response = "";
		$row_number = 1;
		foreach ($array as $row_key => $row) {
			$response .= '<' . $sub_tree . ' no="' . $row_number++ . '">';
			foreach ($row as $fl_key => $fl_value) {
				if (!empty($fl_value)) {
					if (preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $fl_value)) {
						$response .= '<FL val="' . $fl_key . '"><![CDATA[' . $fl_value . ']]></FL>';
					} else {
						$response .= '<FL val="' . $fl_key . '">' . $fl_value . '</FL>';
					}
				}
			}
			$response .= "</$sub_tree>";
		}
		return $response;
	}

	public function getItemsParameters($iterable, $primary = false) {
		$end_items = [];
		if ($iterable && is_array($iterable)) {
			foreach ($iterable as $item) {
				$end_item = [];
				foreach ($item['FL'] as $fl) {
					if (isset($fl['content'])) {
						$end_item[$fl['val']] = $fl['content'];
					} elseif (!empty($fl['product'])) {
						if (isset($fl['product']["FL"])) {
							$product_iterable = [$fl['product']];
						} else {
							$product_iterable = $fl['product'];
						}
						$end_item['products'] = $this->getItemsParameters($product_iterable, 'Product Id');

					}
				}
				if ($primary && isset($end_item[$primary])) {
					$end_items[$end_item[$primary]] = $end_item;
				} else {
					$end_items[] = $end_item;
				}
			}
			return $end_items;
		} else {
			return $iterable;
		}
	}
	public function processInsertOneResponse(string $response) {
		if ($response = \GuzzleHttp\json_decode($response, true)) {
			if ($response = $response['response']['result']['recorddetail'] ?? null) {
				if (!empty($recordDetails = $this->getItemsParameters([$response])) && (!empty($recordDetails = current($recordDetails)))) {
					return $recordDetails;
				}
			}
		}
		return null;
	}

}