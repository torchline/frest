<?php
/**
 * Created by Brad Walker on 5/5/16 at 4:15 PM
*/

namespace FREST\Resource;

class RequestConstructor
{
	/**
	 * @param \FREST\URL\Request $urlRequest
	 * @return mixed
	 */
	public static function constructRequestFromURLRequest($urlRequest)
	{
		$resourceRequest = NULL;

		switch ($urlRequest->getMethod()) {
			case 'GET':
				$resourceRequest = self::constructReadRequestFromURLRequest($urlRequest);
				break;
			case 'POST':
				$resourceRequest = self::constructCreateRequestFromURLRequest($urlRequest);
				break;
			case 'PUT':
				$resourceRequest = self::constructUpdateRequestFromURLRequest($urlRequest);
				break;
			case 'DELETE':
				$resourceRequest = self::constructDeleteRequestFromURLRequest($urlRequest);
				break;
			default:
				// TODO: exception
				break;
		}

		return $resourceRequest;
	}

	/**
	 * @param \FREST\URL\Request $urlRequest
	 * @return Request\Read
	 */
	protected static function constructReadRequestWithParameters($urlRequest)
	{
		$parameters = $urlRequest->getParameters();
		$resourceID = $urlRequest->getResourceID();

		$fields = NULL;
		$filters = NULL;
		$orderBys = NULL;
		$limit = NULL;
		$offset = NULL;

		if (isset($resourceID)) {
			$limit = 1;
			$filters['id'] = $resourceID;
		} else {
			$filters = $parameters;

			if (isset($parameters['limit'])) {
				// TODO: typecheck
				$limit = intval($parameters['limit']);
				unset($filters['limit']);
			}
			if (isset($parameters['offset'])) {
				// TODO: typecheck
				$offset = intval($parameters['offset']);
				unset($filters['offset']);
			}
			if (isset($parameters['orderBy'])) {
				$orderBys = explode(',', $parameters['orderBy']);
				unset($filters['orderBy']);
			}
		}

		if (isset($parameters['fields'])) {
			$fields = explode(',', $parameters['fields']);
			unset($filters['fields']);
		}

		$resourceRequest = new Request\Read(
			$urlRequest->getResourceName(),
			$fields,
			$filters,
			$orderBys,
			$limit,
			$offset
		);
		return $resourceRequest;
	}

	/**
	 * @param \FREST\URL\Request $urlRequest
	 * @return Request\Create
	 */
	protected static function constructCreateRequestFromURLRequest($urlRequest)
	{
		// TODO: URL\Request to Resource\Request\Create
	}

	/**
	 * @param \FREST\URL\Request $urlRequest
	 * @return Request\Update
	 */
	protected static function constructUpdateRequestFromURLRequest($urlRequest)
	{
		// TODO: URL\Request to Resource\Request\Update
	}

	/**
	 * @param \FREST\URL\Request $urlRequest
	 * @return Request\Delete
	 */
	protected static function constructDeleteRequestFromURLRequest($urlRequest)
	{
		// TODO: URL\Request to Resource\Request\Delete
	}
}