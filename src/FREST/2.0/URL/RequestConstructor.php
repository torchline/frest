<?php
/**
 * Created by Brad Walker on 5/5/16 at 5:42 PM
*/

namespace FREST\URL;

class RequestConstructor 
{
	/**
	 * @param string $method
	 * @param string $url
	 * @param array $parameters
	 * @return Request
	 */
	public static function constructRequestFromURL($method, $url, $parameters)
	{
		// TODO: safety checking
		$resourceInfo = self::inferResourceInfoFromURL($url); // url cannot have ?
		$resourceName = $resourceInfo['name'];
		$resourceID = $resourceInfo['id'];

		return new Request($method, $resourceName, $resourceID, $parameters);
	}

	/**
	 * @return Request
	 */
	public static function constructRequestFromGlobalState()
	{
		$url = self::getURLWithoutParametersFromURL($_SERVER['REQUEST_URI']);
		$method = $_SERVER['REQUEST_METHOD'];

		$getParameters = $_GET;
		$postParameters = $_POST;

		if ($getParameters['method']) {
			$method = strtoupper($getParameters['method']);
			unset($getParameters['method']);
		}

		$parameters = $method === 'GET' ? $getParameters : $postParameters;

		return self::constructRequestFromURL($url, $method, $parameters);
	}

	/**
	 * @param string $url
	 * @return array
	 */
	protected static function inferResourceInfoFromURL($url)
	{
		$components = explode('/', $url);
		$count = count($components);
		if ($count == 0) {
			return NULL;
		}
		else if ($count == 1) {
			return ['name' => $components[0]];
		}

		$lastComponent = $components[$count-1];
		$nextToLastComponent = $components[$count-2];

		if (is_numeric($lastComponent) && strpos($lastComponent, '.') == NULL) {
			$resourceInfo['name'] = $nextToLastComponent;
			$resourceInfo['id'] = intval($lastComponent);
		}
		else {
			$resourceInfo['name'] = $lastComponent;
		}

		return $resourceInfo;
	}

	/**
	 * @param string $url
	 * @return string
	 */
	protected static function getURLWithoutParametersFromURL($url)
	{
		$queryPosition = strpos($url, '?');
		if ($queryPosition !== FALSE) {
			$urlWithoutParameters = substr($url, 0, $queryPosition);
		}
		else {
			$urlWithoutParameters = $url;
		}

		return $urlWithoutParameters;
	}
} 