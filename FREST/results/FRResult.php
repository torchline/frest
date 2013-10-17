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

		if ($frest->getConfig()->getShowDiagnostics()) {
			$this->outputObject->diagnostics = new stdClass;
			$this->outputObject->diagnostics->timing = $frest->getTimingObject();
			$this->outputObject->diagnostics->memory = number_format((memory_get_peak_usage(TRUE) / 1000 / 1000), 3) . 'mb';
		}
		
		switch ($format) {
			case FROutputFormat::JSON:
				$output = json_encode($this->outputObject);
				break;
			case FROutputFormat::JSONP:
				$output = 'callback('.json_encode($this->outputObject).')';
				break;
			case FROutputFormat::XML:
				$output = '<root>not yet implemented</root>';
				break;
			case FROutputFormat::_ARRAY:
				$output = get_object_vars($this->outputObject);
				$inline = TRUE;
				break;
			case FROutputFormat::OBJECT:
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
			header('Content-Type: ' . FROutputFormat::contentTypeString($format));

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
		}
	}
	

	/**
	 * @return int
	 */
	public function getHttpStatusCode() {
		return $this->httpStatusCode;
	}
}