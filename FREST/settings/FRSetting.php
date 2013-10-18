<?php
/**
 * Created by Brad Walker on 9/30/13 at 12:28 AM
*/

require_once(dirname(__FILE__).'/../settings/FRTableSetting.php');
require_once(dirname(__FILE__).'/../settings/FRFieldSetting.php');

require_once(dirname(__FILE__).'/../settings/FRFieldReadSetting.php');
require_once(dirname(__FILE__) . '/../settings/FRSingularResourceReadSetting.php');
require_once(dirname(__FILE__) . '/../settings/FRPluralResourceReadSetting.php');
require_once(dirname(__FILE__).'/../settings/FRComputedReadSetting.php');

require_once(dirname(__FILE__).'/../settings/FRConditionSetting.php');
require_once(dirname(__FILE__).'/../settings/FROrderSetting.php');
require_once(dirname(__FILE__).'/../settings/FRCreateSetting.php');
require_once(dirname(__FILE__).'/../settings/FRUpdateSetting.php');

/**
 * Class FRSetting
 */
class FRSetting {
	/*** META ***/
	static function table($table, $fieldSettings) {
		return new FRTableSetting($table, $fieldSettings);
	}

	static function field($alias, $field, $variableType = FRVariableType::STRING) {
		return new FRFieldSetting($alias, $field, $variableType);
	}
	
	
	/*** READ ***/
	static function readField($alias, $filterFunction = NULL, $default = TRUE) {
		return new FRFieldReadSetting($alias, $filterFunction, $default);
	}

	static function readResource($alias, $resourceName, $resourceJoinAlias, $aliasesToRead = NULL, $default = FALSE) {
		return new FRSingularResourceReadSetting($alias, $resourceName, $resourceJoinAlias, $aliasesToRead, $default);
	}

	static function readResources($alias, $resourceName, $parameters, $default = FALSE) {
		return new FRPluralResourceReadSetting($alias, $resourceName, $parameters, $default);
	}

	static function readFunction($alias, $function, $requiredAliases = NULL, $default = FALSE) {
		return new FRComputedReadSetting($alias, $function, $requiredAliases, $default);
	}
	
	
	/*** CONDITION ***/
	static function condition($alias) {
		return new FRConditionSetting($alias);
	}


	/*** ORDER ***/
	static function order($alias, $ascendingEnabled = TRUE, $descendingEnabled = TRUE) {
		return new FROrderSetting($alias, $ascendingEnabled, $descendingEnabled);
	}


	/*** CREATE ***/
	static function create($alias, $required = TRUE, $conditionFunction = NULL, $filterFunction = NULL) {
		return new FRCreateSetting($alias, $required, $conditionFunction, $filterFunction);
	}


	/*** UPDATE ***/
	static function update($alias, $conditionFunction = NULL, $filterFunction = NULL) {
		return new FRUpdateSetting($alias, $conditionFunction, $filterFunction);
	}
}