<?php
/**
 * Created by Brad Walker on 10/10/13 at 8:05 PM
*/

use FREST\Resource;
use FREST\Setting\Settings;
use FREST\Type\Variable;

/**
 * Class Ranks
 */
class Ranks extends Resource {
	public function setup() {
		$this->setTableSettings(array(
			Settings::table('rank', array(
				Settings::field('id', 'ID', Variable::INT),
				Settings::field('name', 'Name', Variable::STRING),
				Settings::field('ordinal', 'Ordinal', Variable::INT),
			))
		));
	}
}