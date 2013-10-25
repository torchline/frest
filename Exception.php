<?php
/**
 * Created by Brad Walker on 10/24/13 at 6:50 PM
*/

namespace FREST;

/**
 * Class Exception
 * @package FREST
 */
class Exception extends \Exception {

	const InvalidField = 1401;
	const Config = 1402;
	const InvalidType = 1403;
	const InvalidValue = 1404;
	const InvalidUsage = 1405;
	const ValueAlreadyTaken = 1406;
	const MissingRequiredParams = 1407;
	const SQLError = 1408;
	const NoResults = 1409;
	const ComputationFunctionMissing = 1410;
	const InvalidMethod = 1411;
	const ConditionFunctionMissing = 1412;
	const FilterFunctionMissing = 1413;
	const InvalidFieldValue = 1414;
	const MissingResourceID = 1415;
	const PresentResourceID = 1416;
	const ResourceFunctionDoesntExist = 1417;
	const ResourceFunctionMissing = 1418;
	const InvalidFunctionParameter = 1419;
	const MissingRequiredFunctionParameter = 1420;
	const MismatchingResourceFunctionMethod = 1421;
	const PartialSyntaxNotSupported = 1422;
	const NothingToDo = 1423;
	const FailedLoadingResource = 1424;
	const WildcardsNotAllowed = 1425;
	const PartialSyntaxNotAllowed = 1426;
	const FieldsParameterNotAllowed = 1427;

	private static $httpCodes = array(
		self::InvalidField => 400,
		self::Config => 500,
		self::InvalidType => 400,
		self::InvalidValue => 400,
		self::InvalidUsage => 400,
		self::ValueAlreadyTaken => 400,
		self::MissingRequiredParams => 400,
		self::SQLError => 500,
		self::NoResults => 404,
		self::ComputationFunctionMissing => 500,
		self::InvalidMethod => 400,
		self::ConditionFunctionMissing => 500,
		self::FilterFunctionMissing => 500,
		self::InvalidFieldValue => 400,
		self::MissingResourceID => 400,
		self::PresentResourceID => 400,
		self::ResourceFunctionDoesntExist => 400,
		self::ResourceFunctionMissing => 500,
		self::InvalidFunctionParameter => 400,
		self::MissingRequiredFunctionParameter => 400,
		self::MismatchingResourceFunctionMethod => 400,
		self::PartialSyntaxNotSupported => 400,
		self::NothingToDo => 400,
		self::FailedLoadingResource => 500,
		self::WildcardsNotAllowed => 400,
		self::PartialSyntaxNotAllowed => 400,
		self::FieldsParameterNotAllowed => 400,
	);
	
	private static $descriptions = array(
		self::InvalidField => 'There is an invalid field specified in the query',
		self::Config => 'The API is not configured correctly. Please notify a site admin ASAP',
		self::InvalidType => 'There is a type conflict in one of the query parameters',
		self::InvalidValue => 'There is an invalid value in one of the query parameters',
		self::InvalidUsage => 'The API is not supposed to be used like that',
		self::ValueAlreadyTaken => 'The value specified cannot be used because it has already been taken',
		self::MissingRequiredParams => 'Additional parameters are required',
		self::SQLError => 'There was an error querying the database',
		self::NoResults => 'No Result were found',
		self::ComputationFunctionMissing => 'Function for computed read setting missing',
		self::InvalidMethod => 'That HTTP method is not supported',
		self::ConditionFunctionMissing => 'Condition Func implementation missing in resource',
		self::FilterFunctionMissing => 'Filter Func implementation missing in resource',
		self::InvalidFieldValue => 'A value does not meet the requirements for its field',
		self::MissingResourceID => 'Resource ID missing from query',
		self::PresentResourceID => 'Resource ID present in query but should not be',
		self::ResourceFunctionDoesntExist => 'Resource Func specified does not exist in resource',
		self::ResourceFunctionMissing => 'Resource Func implementation missing in resource',
		self::InvalidFunctionParameter => 'There is an invalid parameter specified in the query',
		self::MissingRequiredFunctionParameter => 'Additional parameters are required for the Func',
		self::MismatchingResourceFunctionMethod => 'The method used for the query does not match the method required by the Func specified',
		self::PartialSyntaxNotSupported => 'Partial syntax was attempted on a field that does not support it',
		self::NothingToDo => 'There is nothing to do',
		self::FailedLoadingResource => 'There is an error in the resource file',
		self::WildcardsNotAllowed => 'Wildcard are not allowed on this resource',
		self::PartialSyntaxNotAllowed => 'Partial syntax is not allowed on this resource',
		self::FieldsParameterNotAllowed => 'The "fields" parameter is not allowed on this resource',
	);

	/**
	 * @param int $code
	 * @param string $detailedMessage
	 */
	function __construct($code, $detailedMessage = NULL) {
		$defaultMessage = self::$descriptions[$code];
		if (isset($detailedMessage)) {
			$message = "{$defaultMessage}. {$detailedMessage}.";
		}
		else {
			$message = $defaultMessage;
		}
		
		parent::__construct($message, $code, NULL);
	}

	/**
	 * @return Result\Error
	 */
	public function generateError() {
		return new Result\Error($this->code, self::$httpCodes[$this->code], $this->message);
	}
} 