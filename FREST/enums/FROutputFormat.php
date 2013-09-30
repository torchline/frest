<?php
/**
 * Created by Brad Walker on 6/6/13 at 3:31 PM
*/

final class FROutputFormat {
	const JSON = 1;
	const JSONP = 2;
	const XML = 3;
	
	private function __construct() {}

	/**
	 * @param int $format
	 * 
	 * @return string
	 */
	public static function contentTypeString($format) {
		switch ($format) {
			case self::JSON:
				return 'application/json';
				break;
			case self::JSONP:
				return 'application/javascript';
				break;
			case self::XML:
				return 'application/xml';
				break;
		}
		
		return NULL;
	}
}