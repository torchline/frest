<?php
/**
 * Created by Brad Walker on 9/30/13 at 12:45 AM
*/

require_once(dirname(__FILE__).'/../functions/FRConditionFunction.php');
require_once(dirname(__FILE__).'/../functions/FRFunctionParameter.php');
require_once(dirname(__FILE__).'/../functions/FRResourceFunction.php');

class FRFunction {
	static function condition($name, $sqlOperator, $parameters, $replacements = NULL) {
		return new FRConditionFunction($name, $sqlOperator, $parameters, $replacements);
	}

	static function parameter($variableType, $required = TRUE) {
		return new FRFunctionParameter($variableType, $required);
	}

	static function resource($name, $requiresResourceID, $method, $parameters) {
		return new FRResourceFunction($name, $requiresResourceID, $method, $parameters);
	}
}