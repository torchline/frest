<?php
/**
 * Created by Brad Walker on 10/13/13 at 8:52 PM
*/

namespace FREST\Enum;

/**
 * Class Timing
 * @package FREST\Enum
 */
class Timing {
	const SETUP = 'setup';
	const PROCESSING = 'processing';
	const SQL = 'sql';
	const POST_PROCESSING = 'postProcessing';
	const TOTAL = 'total';

	/**
	 *
	 */
	private function __construct() {}
}