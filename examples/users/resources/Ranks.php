<?php
/**
 * Created by Brad Walker on 10/10/13 at 8:05 PM
*/

require_once(dirname(__FILE__) . '/../../../FREST/Resource.php');

use FREST\Resource;
use FREST\Setting\Settings;
use FREST\Enum\VariableType;

/**
 * Class Ranks
 */
class Ranks extends Resource {
	public function setup() {
		$this->setTableSettings(array(
			Settings::table('rank', array(
				Settings::field('id', 'ID', VariableType::INT),
				Settings::field('name', 'Name', VariableType::STRING),
				Settings::field('ordinal', 'Ordinal', VariableType::INT),
			))
		));
	}
}