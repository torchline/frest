<?php
/**
 * Created by Brad Walker on 6/4/13 at 4:16 PM
*/

require_once(dirname(__FILE__)."/FRResult.php");

class FRErrorResult extends FRResult {
	
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
	
	private static $descriptions = array(
		self::InvalidField => 'There is an invalid field specified in the query',
		self::Config => 'The API is not configured correctly. Please notify a site admin ASAP',
		self::InvalidType => 'There is a type conflict in one of the query parameters',
		self::InvalidValue => 'There is an invalid value in one of the query parameters',
		self::InvalidUsage => 'The API is not supposed to be used like that',
		self::ValueAlreadyTaken => 'The value specified cannot be used because it has already been taken',
		self::MissingRequiredParams => 'Additional parameters are required',
		self::SQLError => 'There was an error querying the database',
		self::NoResults => 'No results were found',
		self::ComputationFunctionMissing => 'Function for computed read setting missing',
		self::InvalidMethod => 'That HTTP method is not supported',
		self::ConditionFunctionMissing => 'Condition function implementation missing in resource',
		self::FilterFunctionMissing => 'Filter function implementation missing in resource',
		self::InvalidFieldValue => 'A value does not meet the requirements for its field',
		self::MissingResourceID => 'Resource ID missing from query',
		self::PresentResourceID => 'Resource ID present in query but should not be',
		self::ResourceFunctionDoesntExist => 'Resource function specified does not exist in resource',
		self::ResourceFunctionMissing => 'Resource function implementation missing in resource',
		self::InvalidFunctionParameter => 'There is an invalid parameter specified in the query',
		self::MissingRequiredFunctionParameter => 'Additional parameters are required for the function',
		self::MismatchingResourceFunctionMethod => 'The method used for the query does not match the method required by the function specified'
	);

	/** @var int */
	protected $code;
	
	/** @var string */
	protected $detailedDescription;
	
	/** @var string */
	protected $userMessage = NULL;

	function __construct($code, $httpStatusCode, $detailedDescription, $userMessage = NULL) {
		$this->code = $code;
		$this->httpStatusCode = $httpStatusCode;
		$this->detailedDescription = $detailedDescription;
		$this->userMessage = $userMessage;
	}
	
	protected function generateOutputObject() {
		$outputObject = new stdClass();
		$outputObject->code = $this->code;
		$outputObject->status = $this->httpStatusCode;
		$outputObject->developerMessage = $this->getDescription() . '. ' . $this->detailedDescription;

		if (isset($this->userMessage)) {
			$outputObject->userMessage = $this->userMessage;
		}

		return $outputObject;
	}
	

	/**
	 * @return int
	 */
	public function getCode() {
		return $this->code;
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return self::$descriptions[$this->code];
	}
	
	/**
	 * @return string
	 */
	public function getDetailedDescription() {
		return $this->detailedDescription;
	}

	/**
	 * @return string
	 */
	public function getUserMessage() {
		return $this->userMessage;
	}
	
}