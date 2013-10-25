<?php
/**
 * Created by Brad Walker on 6/5/13 at 1:50 PM
*/

namespace FREST\Result;

use FREST;
use FREST\Enum;

require_once(dirname(__FILE__) . '/../Enum/OutputFormat.php');

/**
 * Class Result
 * @package FREST\Result
 */
abstract class Result {
	
	// TODO: static getters here

	/** @var int */
	protected $httpStatusCode;

	/** @var \stdClass */
	private $outputObject;

	/** @var FREST */
	protected $frest;


	/**
	 * @return \stdClass
	 */
	protected abstract function generateOutputObject();
	
	/**
	 * @param FREST $frest
	 * @param int $format
	 * @param bool $inline
	 * 
	 * @return string
	 */
	public function output($frest, $format = Enum\OutputFormat::JSON, $inline = FALSE) {
		$this->outputObject = $this->generateOutputObject();

		if ($frest->getConfig()->getShowDiagnostics()) {
			$this->outputObject->diagnostics = new \stdClass;
			$this->outputObject->diagnostics->timing = $frest->getTimingObject();
			$this->outputObject->diagnostics->memory = number_format((memory_get_peak_usage(TRUE) / 1000 / 1000), 3) . 'mb';
		}
		
		switch ($format) {
			case Enum\OutputFormat::JSON:
				$output = json_encode($this->outputObject);
				break;
			case Enum\OutputFormat::JSONP:
				$output = 'callback('.json_encode($this->outputObject).')';
				break;
			case Enum\OutputFormat::XML:
				$output = '<root>not yet implemented</root>';
				break;
			case Enum\OutputFormat::_ARRAY:
				$output = get_object_vars($this->outputObject);
				$inline = TRUE;
				break;
			case Enum\OutputFormat::OBJECT:
				$output = $this->outputObject;
				$inline = TRUE;
				break;
			default:
				$output = 'invalid output format';
				break;
		}
		
		if ($inline) {
			return $output;
		}
		else {
			$headerStatusCode = $frest->getSuppressHTTPStatusCodes() ? 200 : $this->httpStatusCode;

			header('HTTP/1.1: ' . $headerStatusCode);
			header('Status: ' . $headerStatusCode);
			header('Content-Type: ' . Enum\OutputFormat::contentTypeString($format));

			if (extension_loaded('zlib') && isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE) {
				//ob_end_clean();
				//ob_start('ob_gzhandler');
			}
			else if ($output) {
				header('Content-Length: ' . strlen($output));
			}
			
			if (is_string($output)) {
				echo $output;
			}
			else {
				var_dump($output);
			}
			
			return NULL;
		}
	}
	

	/**
	 * @return int
	 */
	public function getHttpStatusCode() {
		return $this->httpStatusCode;
	}
}