<?php
/**
 * Created by Brad Walker on 9/30/13 at 12:28 AM
*/

namespace FREST\Setting;

use FREST\Type;

/**
 * Class Settings
 * @package FREST\Setting
 */
class Settings {
	/*** META ***/
	
	/**
	 * @param string $table
	 * @param array $fieldSettings
	 * @return Table
	 */
	static function table($table, $fieldSettings) {
		return new Table($table, $fieldSettings);
	}

	/**
	 * @param string $alias
	 * @param string $field
	 * @param int $variableType
	 * @return Field
	 */
	static function field($alias, $field, $variableType = Type\Variable::STRING) {
		return new Field($alias, $field, $variableType);
	}
	
	
	/*** READ ***/

	/**
	 * @param string $alias
	 * @param string|NULL $filterFunction
	 * @param bool $default
	 * @return FieldRead
	 */
	static function readField($alias, $filterFunction = NULL, $default = TRUE) {
		return new FieldRead($alias, $filterFunction, $default);
	}

	/**
	 * @param string $alias
	 * @param string $resourceName
	 * @param string $resourceJoinAlias
	 * @param array|NULL $aliasesToRead
	 * @param bool $default
	 * @return SingularResourceRead
	 */
	static function readResource($alias, $resourceName, $resourceJoinAlias, $aliasesToRead = NULL, $default = FALSE) {
		return new SingularResourceRead($alias, $resourceName, $resourceJoinAlias, $aliasesToRead, $default);
	}

	/**
	 * @param string $alias
	 * @param string $resourceName
	 * @param array $parameters
	 * @param bool $default
	 * @return PluralResourceRead
	 */
	static function readResources($alias, $resourceName, $parameters, $default = FALSE) {
		return new PluralResourceRead($alias, $resourceName, $parameters, $default);
	}

	/**
	 * @param string $alias
	 * @param string $function
	 * @param array|NULL $requiredAliases
	 * @param bool $default
	 * @return ComputedRead
	 */
	static function readFunction($alias, $function, $requiredAliases = NULL, $default = FALSE) {
		return new ComputedRead($alias, $function, $requiredAliases, $default);
	}
	
	
	/*** CONDITION ***/

	/**
	 * @param string $alias
	 * @return Condition
	 */
	static function condition($alias) {
		return new Condition($alias);
	}


	/*** ORDER ***/

	/**
	 * @param string $alias
	 * @param bool $ascendingEnabled
	 * @param bool $descendingEnabled
	 * @return Order
	 */
	static function order($alias, $ascendingEnabled = TRUE, $descendingEnabled = TRUE) {
		return new Order($alias, $ascendingEnabled, $descendingEnabled);
	}


	/*** CREATE ***/

	/**
	 * @param string $alias
	 * @param bool $required
	 * @param string|NULL $conditionFunction
	 * @param string|NULL $filterFunction
	 * @return Create
	 */
	static function create($alias, $required = TRUE, $conditionFunction = NULL, $filterFunction = NULL) {
		return new Create($alias, $required, $conditionFunction, $filterFunction);
	}


	/*** UPDATE ***/

	/**
	 * @param string $alias
	 * @param string|NULL $conditionFunction
	 * @param string|NULL $filterFunction
	 * @return Update
	 */
	static function update($alias, $conditionFunction = NULL, $filterFunction = NULL) {
		return new Update($alias, $conditionFunction, $filterFunction);
	}
}