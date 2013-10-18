<?php
/**
 * Created by Brad Walker on 10/10/13 at 8:05 PM
*/

require_once(dirname(__FILE__).'/../../../FREST/resources/FRResource.php');

class Ranks extends FRResource {
	public function setup() {
		$this->setTableSettings(array(
			FRSetting::table('rank', array(
				FRSetting::field('id', 'ID', FRVariableType::INT),
				FRSetting::field('name', 'Name', FRVariableType::STRING),
				FRSetting::field('ordinal', 'Ordinal', FRVariableType::INT),
			))
		));
	}
}