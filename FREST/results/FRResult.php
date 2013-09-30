<?php
/**
 * Created by Brad Walker on 6/5/13 at 1:50 PM
*/

require_once(dirname(__FILE__).'/../enums/FROutputFormat.php');

abstract class FRResult {
	
	// TODO: static getters here

	/** @var int */
	protected $httpStatusCode;

	/** @var stdClass */
	private $outputObject;

	/** @var FREST */
	protected $frest;

	function __construct($frest, $httpStatusCode)
	{
		$this->frest = $frest;
		$this->httpStatusCode = $httpStatusCode;
	}


	/**
	 * @return stdClass
	 */
	protected abstract function generateOutputObject();
	
	/**
	 * @param FREST $frest
	 * @param int $format
	 * @param bool $inline
	 * 
	 * @return string
	 */
	public function output($frest, $format = FROutputFormat::JSON, $inline = FALSE) {
		$this->outputObject = $this->generateOutputObject();
		
		switch ($format) {
			case FROutputFormat::JSON:
				$outputString = json_encode($this->outputObject);
				break;
			case FROutputFormat::JSONP:
				$outputString = 'callback('.json_encode($this->outputObject).')';
				break;
			case FROutputFormat::XML:
				$outputString = '<root>not yet implemented</root>';
				break;
			default:
				$outputString = 'invalid output format';
				break;
		}
		
		if ($inline) {
			return $outputString;
		}
		else {
			$headerStatusCode = $frest->getSuppressHTTPStatusCodes() ? 200 : $this->httpStatusCode;
			
			header('HTTP/1.1: ' . $headerStatusCode);
			header('Status: ' . $this->httpStatusCode);
			header('Content-Type: ' . FROutputFormat::contentTypeString($format));

			if (extension_loaded('zlib') && isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE) {
				//ob_end_clean();
				//ob_start('ob_gzhandler');
			}
			else {
				header('Content-Length: ' . strlen($outputString));
			}
			
			die($outputString);
		}
	}
	

	/**
	 * @return int
	 */
	public function getHttpStatusCode() {
		return $this->httpStatusCode;
	}
}