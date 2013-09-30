<?php
/**
 * Created by Brad Walker on 8/16/13 at 9:22 PM
*/

final class FRMethod {
	const GET = 1;
	const POST = 2;
	const PUT = 3;
	const DELETE = 4;

	private function __construct() {}


	/**
	 * @param int $method
	 * @return string
	 */
	public static function getString($method) {
		switch ($method) {
			case self::GET:
				return 'GET';
				break;
			case self::POST:
				return 'POST';
				break;
			case self::PUT:
				return 'PUT';
				break;
			case self::DELETE:
				return 'DELETE';
				break;
		}
		
		return NULL;
	}
}