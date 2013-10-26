<?php
/**
 * Created by Brad Walker on 9/30/13 at 12:45 AM
*/

namespace FREST\Func;

/**
 * Class Functions
 * @package Router\Func
 */
class Functions {
	/**
	 * @param string $name
	 * @param string $sqlOperator
	 * @param array $parameters
	 * @param array|NULL $replacements
	 * @return Condition
	 */
	static function condition($name, $sqlOperator, $parameters, $replacements = NULL) {
		return new Condition($name, $sqlOperator, $parameters, $replacements);
	}

	/**
	 * @param string $name
	 * @param bool $requiresResourceID
	 * @param int $method
	 * @param array $parameters
	 * @return Resource
	 */
	static function resource($name, $requiresResourceID, $method, $parameters) {
		return new Resource($name, $requiresResourceID, $method, $parameters);
	}

	/**
	 * @param int $variableType
	 * @param bool $required
	 * @return FunctionParam
	 */
	static function parameter($variableType, $required = TRUE) {
		return new FunctionParam($variableType, $required);
	}
}