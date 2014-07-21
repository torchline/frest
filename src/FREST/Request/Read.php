<?php
/**
 * Created by Brad Walker on 6/6/13 at 1:42 PM
*/

namespace FREST\Request;

use FREST;
use FREST\Request;
use FREST\Type;
use FREST\Result;
use FREST\Setting;
use FREST\Spec;

/**
 * Class Read
 * @package FREST\Request
 */
abstract class Read extends Request\Request {
	
	protected static $loadedResources = array();

	protected $partialSubReadSettings = array();

	protected $requiredAliasesAdded = array();
		
	/** @var array */
	protected $readSettings;
	
	/** @var array */
	protected $joinSpecs;
	
	/** @var array */
	protected $fieldSpecs;
	
	/** @var array */
	protected $tableSpecs;

	/** @var array */
	protected $tableAbbreviations = array();

	/** @var array */
	protected $joinTableAbbreviations = array();
	
	/** @var array */
	protected $loadedResourceReadSettings = array();
	
	protected $parentAlias;

	/**
	 * @param FREST\Router $frest
	 * @param string $resourceID
	 * @param array $parameters
	 * @param string $resourceFunctionName
	 * @param string $parentAlias
	 */
	public function __construct($frest, $resourceID = NULL, $parameters, $resourceFunctionName = NULL, $parentAlias = NULL) {
		$this->miscParameters['fields'] = TRUE;
		
		$this->parentAlias = $parentAlias;

		/** @noinspection PhpUndefinedClassInspection */
		parent::__construct($frest, $resourceID, $parameters, $resourceFunctionName);
	}

	/**
	 * @param FREST\Resource $resource
	 * @throws FREST\Exception
	 */
	public function setupWithResource($resource) {
		/** @noinspection PhpUndefinedClassInspection */
		parent::setupWithResource($resource);
		
		$this->readSettings = $this->generateReadSettings($this->resource, $this->parameters);
		if (!isset($this->readSettings)) {
			throw new FREST\Exception(FREST\Exception::Config, "No read Setting exist or none are default");
		}
		
		$this->joinSpecs = $this->generateJoinSpecs($this->resource, $this->readSettings);
		$this->fieldSpecs = $this->generateFieldSpecs($this->resource, $this->readSettings);
		
		$this->tableSpecs = $this->generateTableSpecs($this->resource, $this->readSettings);
	}

	/**
	 * @param FREST\Resource $resource
	 * @param array $parameters
	 * @param string $partialPrefix
	 *
	 * @return array|NULL
	 * @throws FREST\Exception
	 */
	protected function generateReadSettings($resource, $parameters, $partialPrefix = NULL) {
		$readSettings = array();

		$allReadSettings = $resource->getReadSettings();
		
		if (isset($parameters['fields'])) {
			$userSpecifiedAliases = $this->parseFieldParameterList($parameters['fields']);
			
			if (isset($userSpecifiedAliases)) {
				$hasWildcard = count($userSpecifiedAliases) > 0 && $userSpecifiedAliases[0] == '*'; // must be specified first
				if ($hasWildcard) { // if wildcard, then all fields
					$readSettings = $resource->getReadSettings();
					unset($userSpecifiedAliases[0]); // if other fields specified after wildcard, then allow them if partial
				}
				static $borg = 0;
				$borg++;
				if ($borg > 1) {
					echo '<pre>'; var_dump($userSpecifiedAliases); die();
				}

				foreach ($userSpecifiedAliases as $alias) {
					if ($alias == '*') {
						throw new FREST\Exception(FREST\Exception::InvalidUsage, "Wildcard field parameter must be specified before any others (for readability).");
					}

					$readSetting = isset($allReadSettings[$alias]) ? $allReadSettings[$alias] : NULL;

					if (isset($readSetting)) {
						if ($hasWildcard) {
							throw new FREST\Exception(FREST\Exception::InvalidUsage, "Fields specified after a wildcard that do not contain partial syntax are unnecessary");
						}

						$readSettings[$alias] = $readSetting;
					}
					else {
						$partialReadSetting = $this->generatePartialReadSetting($resource, $alias, $partialPrefix);

						if (isset($partialReadSetting)) {
							$aliasFromPartial = $partialReadSetting->getAlias();
							$readSettings[$aliasFromPartial] = $partialReadSetting;
						}
					}
				}
			}			
		}
		else {
			// gather all aliases set to be read by default if none are specified by the client
			/** @var Setting\Read $readSetting */
			foreach ($allReadSettings as $alias=>$readSetting) {
				if ($readSetting->getDefault()) {
					$readSettings[$alias] = $readSetting;
				}
			}
		}

		if (count($readSettings) > 0) {
			$this->addRequiredReadSettings($resource, $readSettings);
			return $readSettings;
		}

		return NULL;
	}

	/**
	 * @param FREST\Resource $resource
	 * @param string $alias
	 * @param string|NULL $partialPrefix
	 * @return Setting\SingularResourceRead|null
	 * @throws FREST\Exception
	 */
	private function generatePartialReadSetting($resource, $alias, $partialPrefix = NULL) {
		// check for Setting for partial object alias
		$aliasFromPartial = $this->parsePartialAliasFromString($alias, $definedSubAliases);

		if (!isset($aliasFromPartial)) {
			throw new FREST\Exception(FREST\Exception::InvalidField, "Invalid field name specified in 'fields' parameter: '{$alias}' on resource {$resource->getName()}");
		}

		$allReadSettings = $resource->getReadSettings();

		/** @var Setting\SingularResourceRead $readSetting */
		$readSetting = $allReadSettings[$aliasFromPartial];

		if (!isset($readSetting)) {
			throw new FREST\Exception(FREST\Exception::InvalidField, "Invalid field name specified in 'fields' parameter using partial syntax: '{$aliasFromPartial}' on resource {$resource->getName()}");
		}

		if (!($readSetting instanceof Setting\SingularResourceRead) && !($readSetting instanceof Setting\PluralResourceRead)) {
			throw new FREST\Exception(FREST\Exception::PartialSyntaxNotSupported, "The field '{$aliasFromPartial}' on resource {$resource->getName()} does not respond to partial object syntax");
		}

		// load external resource referenced by this resource
		$loadedResource = $this->getLoadedResource($readSetting->getResourceName(), $this);
		$allLoadedResourceReadSettings = $loadedResource->getReadSettings();
		$loadedResourceReadSettings = array();

		// check if the referenced resource has all partial fields specified
		if (isset($definedSubAliases)) {
			foreach ($definedSubAliases as $subAlias) {
				if (!isset($allLoadedResourceReadSettings[$subAlias])) {
					$subAliasFromPartial = $this->parsePartialAliasFromString($subAlias, $deepAliases);

					if (!isset($allLoadedResourceReadSettings[$subAliasFromPartial])) {
						throw new FREST\Exception(FREST\Exception::InvalidField, "Invalid sub-field '{$subAlias}' specified in '{$alias}' on resource {$resource->getName()}");
					}

					$subReadSetting = $allLoadedResourceReadSettings[$subAliasFromPartial];

					if ($subReadSetting instanceof Setting\SingularResourceRead || $subReadSetting instanceof Setting\PluralResourceRead) {
						/** @var Setting\SingularResourceRead|Setting\PluralResourceRead $subReadSetting */

						$subLoadedResource = $this->getLoadedResource($subReadSetting->getResourceName());
						$subPartialPrefix = isset($partialPrefix) ? "{$partialPrefix}.{$aliasFromPartial}.{$subAliasFromPartial}" : "{$aliasFromPartial}.{$subAliasFromPartial}";
						$subReadSettings = $this->generateReadSettings($subLoadedResource, array('fields' => implode(',', $deepAliases)), $subPartialPrefix);

						$this->partialSubReadSettings[$subPartialPrefix] = $subReadSettings;
					}
					else {
						throw new FREST\Exception(FREST\Exception::PartialSyntaxNotSupported, "The field '{$subAliasFromPartial}' on resource {$resource->getName()} does not support partial syntax");
					}

					$subAlias = $subAliasFromPartial;
				}

				$loadedResourceReadSettings[$subAlias] = $allLoadedResourceReadSettings[$subAlias];
			}
		}		

		$loadedPartialKey = isset($partialPrefix) ? "{$partialPrefix}.{$aliasFromPartial}" : $aliasFromPartial;
		$this->partialSubReadSettings[$loadedPartialKey] = $loadedResourceReadSettings;

		return $readSetting;
	}
	
	/**
	 * @param FREST\Resource $resource
	 * @param array $readSettings
	 * @throws FREST\Exception
	 */
	private function addRequiredReadSettings($resource, &$readSettings) {
		$requiredReadSettings = array();
		$resourceName = $resource->getName();
		
		$allReadSettings = $resource->getReadSettings();
		
		// find any aliases necessary for calculation of a computed alias that are not already defined in the read Setting
		foreach ($readSettings as $readSetting) {
			if ($readSetting instanceof Setting\ComputedRead) {
				/** @var Setting\ComputedRead $readSetting */
				
				$requiredAliases = $readSetting->getRequiredAliases();
				
				foreach ($requiredAliases as $requiredAlias) {
					$requiredAliasFromPartial = $this->parsePartialAliasFromString($requiredAlias, $subAliases, FALSE);
					
					// TODO: error check required alias partial
					
					if (!isset($readSettings[$requiredAliasFromPartial]) && !isset($requiredReadSettings[$requiredAliasFromPartial])) {  // if it's not already there
						$requiredReadSetting = $allReadSettings[$requiredAliasFromPartial];
						
						$requiredReadSettings[$requiredAliasFromPartial] = $requiredReadSetting;
						$this->requiredAliasesAdded[$resourceName][$requiredAliasFromPartial] = $requiredAliasFromPartial;
						
						if (isset($subAliases)) {
							if (!($requiredReadSetting instanceof Setting\SingularResourceRead)) {
								throw new FREST\Exception(FREST\Exception::Config, "The required alias '{$requiredAliasFromPartial}' on resource {$resourceName} is not a resource and should contain partial object syntax");
							}

							$loadedResource = $this->getLoadedResource($requiredReadSetting->getResourceName());

							$loadedResourceReadSettings = $loadedResource->getReadSettings();
							
							// find read Setting for partial syntax that were defined in required alias config
							$subReadSettings = array();
							foreach ($subAliases as $subAlias) {
								$subReadSettings[$subAlias] = $loadedResourceReadSettings[$subAlias];
							}

							$this->partialSubReadSettings[$requiredAliasFromPartial] = $subReadSettings;
						}
					}
				}
			}
			else if ($readSetting instanceof Setting\PluralResourceRead) {
				$injectedRequiredAliases = $readSetting->getRequiredAliases();

				foreach ($injectedRequiredAliases as $injectedAlias) {
					if (!isset($allReadSettings[$injectedAlias])) {
						throw new FREST\Exception(FREST\Exception::Config, "Injected alias '{$injectedAlias}' is invalid in resource {$resourceName}");
					}

					if (!isset($readSettings[$injectedAlias]) && !isset($requiredReadSettings[$injectedAlias])) { // if it's not already there
						$requiredReadSettings[$injectedAlias] = $allReadSettings[$injectedAlias];
						$this->requiredAliasesAdded[$resourceName][$injectedAlias] = $injectedAlias;
					}
				}
			}
		}

		if (count($requiredReadSettings) > 0) {
			$readSettings = array_merge($readSettings, $requiredReadSettings);

			$this->addRequiredReadSettings($resource, $readSettings);
		}
	}

	/**
	 * @param FREST\Resource $resource
	 * @param array $readSettings
	 * @param bool $prefixWithTableAbbrv
	 * @param string $resourceAlias
	 *
	 * @return array|NULL
	 */
	protected function generateFieldSpecs($resource, $readSettings, $prefixWithTableAbbrv = FALSE, $resourceAlias = NULL) {
		if (!isset($readSettings)) {
			return NULL;
		}
		
		$fieldSpecs = array();

		foreach ($readSettings as $readSetting) {
			if ($readSetting instanceof Setting\SingularResourceRead) {
				$loadedResource = $this->getLoadedResource($readSetting->getResourceName(), $this);

				$alias = $readSetting->getAlias();
				$partialKey = isset($resourceAlias) ? "{$resourceAlias}.{$alias}" : $alias;
				if (isset($this->partialSubReadSettings[$partialKey])) {
					$subReadSettings = $this->partialSubReadSettings[$partialKey];
					$this->addRequiredReadSettings($loadedResource, $subReadSettings);
				}
				else {
					$subReadSettings = $this->getLoadedResourceReadSettings($loadedResource, $readSetting);
				}
								
				$subResourceAlias = isset($resourceAlias) ? "{$resourceAlias}-{$alias}" : $alias;
				$subFieldSpecs = $this->generateFieldSpecs($loadedResource, $subReadSettings, TRUE, $subResourceAlias);

				if (isset($subFieldSpecs)) {
					$fieldSpecs = array_merge($fieldSpecs, $subFieldSpecs);
				}
			}
			else if ($readSetting instanceof Setting\FieldRead) {
				$alias = $readSetting->getAlias();
				$field = $resource->getFieldForAlias($alias);
				$table = $resource->getTableForField($field);

				$tableKey = isset($resourceAlias) ? "{$table}-{$resourceAlias}" : $table;
				$tableAbbrv = $this->getTableAbbreviation($tableKey);

				if ($prefixWithTableAbbrv) {
					$name = "{$tableAbbrv}_{$alias}";
				}
				else {
					$name = $alias;
				}

				$fieldSpec = new Spec\Field(
					$table,
					$tableAbbrv,
					$field,
					$name
				);

				$fieldSpecs[] = $fieldSpec;
			}
		}

		if (count($fieldSpecs) > 0) {
			return $fieldSpecs;
		}

		return NULL;
	}



	/**
	 * @param array $fieldSpecs
	 *
	 * @return string|NULL
	 */
	protected function generateFieldString($fieldSpecs) {		
		if (!isset($fieldSpecs)) {
			return NULL;
		}
		
		$fields = array();

		/** @var Spec\Field $fieldSpec */
		foreach ($fieldSpecs as $fieldSpec) {
			$tableAbbrv = $fieldSpec->getTableAbbreviation();
			$field = $fieldSpec->getField();
			$name = $fieldSpec->getName();

			$field = "{$tableAbbrv}.{$field}";

			if (isset($name) && $name != $field) {
				$field .= " AS {$name}";
			}

			$fields[] = $field;
		}

		if (count($fields) > 0) {
			return implode(', ', $fields);
		}

		return NULL;
	}

	/**
	 * @param FREST\Resource $resource
	 * @param array $readSettings
	 * @param string $resourceAlias
	 *
	 * @return array|null
	 * @throws FREST\Exception
	 */
	protected function generateJoinSpecs($resource, $readSettings, $resourceAlias = NULL) {
		if (!isset($readSettings)) {
			return NULL;
		}
		
		$joinSpecs = array();

		/** @var Setting\SingularResourceRead $readSetting */
		foreach ($readSettings as $alias=>$readSetting) {
			if ($readSetting instanceof Setting\SingularResourceRead) {
				$loadedResource = $this->getLoadedResource($readSetting->getResourceName(), $this);

				$loadedResourceJoinField = $loadedResource->getFieldForAlias($readSetting->getResourceJoinAlias());
				if (!isset($loadedResourceJoinField)) {
					throw new FREST\Exception(FREST\Exception::Config, "Field not found in resource '{$readSetting->getResourceName()}' for alias '{$readSetting->getResourceJoinAlias()}'");
				}
				
				$loadedResourceTable = $loadedResource->getTableForField($loadedResourceJoinField);
				if (!isset($loadedResourceTable)) {
					throw new FREST\Exception(FREST\Exception::Config, "Table not found in resource '{$readSetting->getResourceName()}' for alias '{$readSetting->getResourceJoinAlias()}'");
				}

				if (isset($this->partialSubReadSettings[$alias])) {
					$subReadSettings = $this->partialSubReadSettings[$alias];
					$this->addRequiredReadSettings($loadedResource, $subReadSettings);
				}
				else {
					$subReadSettings = $this->getLoadedResourceReadSettings($loadedResource, $readSetting);
				}
				
				$subJoinSpecs = $this->generateJoinSpecs($loadedResource, $subReadSettings, $readSetting->getAlias());
				
				$field = $resource->getFieldForAlias($alias);

				$joinSpec = new Spec\Join(
					$resourceAlias,
					$readSetting->getResourceName(),
					$loadedResourceTable,
					$loadedResourceJoinField,
					$field,
					$alias,
					'INNER',
					$subJoinSpecs
				);

				$joinSpecs[$alias] = $joinSpec;
			}
		}

		if (count($joinSpecs) > 0) {
			return $joinSpecs;
		}

		return NULL;
	}

	/**
	 * @param FREST\Resource $resource
	 * @param array $joinSpecs
	 *
	 * @return string
	 */
	protected function generateJoinString($resource, $joinSpecs) {
		if (!isset($joinSpecs)) {
			return '';
		}

		$joinsString = '';

		/** @var Spec\Join $joinSpec */
		foreach ($joinSpecs as $joinSpec) {
			$resourceAlias = $joinSpec->getResourceAlias();

			$alias = $joinSpec->getAlias();
			$joinType = $joinSpec->getType();
			$joinTable = $joinSpec->getTableToJoin();
			$joinTableKey = isset($resourceAlias) ? "{$joinTable}-{$resourceAlias}-{$alias}" : "{$joinTable}-{$alias}";
			$joinTableAbbrv = $this->getTableAbbreviation($joinTableKey);

			$field = $joinSpec->getField();
			$fieldTable = $resource->getTableForField($field);
			$tableKey = isset($resourceAlias) ? "{$fieldTable}-{$resourceAlias}" : $fieldTable;
			$tableAbbrv = $this->getTableAbbreviation($tableKey);
			$joinField = $joinSpec->getFieldToJoin();

			$joinsString .= " {$joinType} JOIN {$joinTable} {$joinTableAbbrv} ON {$tableAbbrv}.{$field} = {$joinTableAbbrv}.{$joinField}";

			$loadedResource = $this->getLoadedResource($joinSpec->getResourceName(), $this);

			$subJoinSpecs = $joinSpec->getSubJoinSpecs();

			if (isset($subJoinSpecs)) {
				$joinsString .= $this->generateJoinString($loadedResource, $subJoinSpecs);
			}
		}

		return $joinsString;
	}

	/**
	 * @param array $tableSpecs
	 *
	 * @return string
	 * @throws FREST\Exception
	 */
	protected function generateTableString($tableSpecs) {
		if (!isset($tableSpecs)) {
			return NULL;
		}
		
		$i = 0;
		$tablesToReadJoinString = '';

		$tableSettings = $this->resource->getTableSettings();

		/** @var Spec\Table $tableSpec */
		foreach ($tableSpecs as $tableSpec) {
			$table = $tableSpec->getTable();
			$tableAbbrv = $tableSpec->getTableAbbreviation();

			if ($i > 0) {
				$onString = '';
				$finished = FALSE;
				$k = 0;

				/** @var Setting\Table $tableSetting */
				foreach ($tableSettings as $tableSetting) {
					$tableForIDField = $tableSetting->getTable();
					$tableAbbrvForIDField = $this->getTableAbbreviation($tableForIDField);

					/** @var Setting\Field $firstFieldSetting */
					$fieldSettings = $tableSetting->getFieldSettings();
					$firstFieldSetting = reset($fieldSettings);
					$idField = $firstFieldSetting->getField();

					if ($k == 0) {
						$onString .= "{$tableAbbrvForIDField}.{$idField} = ";
					}
					else if ($tableAbbrv == $tableAbbrvForIDField) {
						$onString .= "{$tableAbbrvForIDField}.{$idField}";
						$finished = TRUE;
						break;
					}

					$k++;
				}

				if (!$finished) {
					throw new FREST\Exception(FREST\Exception::Config, "Could not find correct table-idField combinations to join");
				}

				$tablesToReadJoinString .= " INNER JOIN {$table} {$tableAbbrv} ON {$onString}";
			}
			else {
				$tablesToReadJoinString = "{$table} {$tableAbbrv}";
			}

			$i++;
		}

		return $tablesToReadJoinString;
	}
	
	/**
	 * Insert any data after object is retrieved such as computed aliases and converting
	 * sub-resource fields into child objects.
	 * 
	 * @param FREST\Resource $resource
	 * @param array $objects
	 * @param array $readSettings
	 * @param string $resourceAlias
	 * 
	 * @throws FREST\Exception
	 */
	protected function parseObjects($resource, &$objects, $readSettings, $resourceAlias = NULL) {					
		// stores the read Setting that are just an ComputedRead
		$computedReadSettings = array();
		
		$timerInstance = isset($resourceAlias) ? $resourceAlias.'-parse' : 'parse';
		
		if (isset($readSettings)) {
			/** @var Setting\Read $readSetting */
			foreach ($readSettings as $readSetting) {
				$this->frest->startTimingForLabel(Type\Timing::POST_PROCESSING, $timerInstance);
	
				$alias = $readSetting->getAlias();
				$partialSubKey = isset($resourceAlias) ? "{$resourceAlias}.{$alias}" : $alias;
	
				if ($readSetting instanceof Setting\ComputedRead) {
					$computedReadSettings[$alias] = $readSetting;
				}
				else if ($readSetting instanceof Setting\PluralResourceRead) {
					/** @var Setting\PluralResourceRead $readSetting */
	
					$parameters = $readSetting->getParameters();
					
					// overwrite 'fields' parameter if partial syntax found
					$newFields = $this->generateNewFields($partialSubKey);
					if (isset($newFields)) {
						$parameters['fields'] = implode(',', $newFields);
					}
					
					$requiredAliases = $readSetting->getRequiredAliases();
	
					$loadedResource = $this->getLoadedResource($readSetting->getResourceName());
					
					foreach ($objects as &$object) {
						$requestParameters = array();
						foreach ($parameters as $field=>$parameter) {
							if (isset($requiredAliases[$field])) {
								$requiredAlias = $requiredAliases[$field];
								$requiredAliasValuePlaceholder = $loadedResource->injectValue($requiredAlias);
								$parameter = str_replace($requiredAliasValuePlaceholder, $object->$requiredAlias, $parameter);
							}
	
							$requestParameters[$field] = $parameter;
						}
	
						$this->frest->stopTimingForLabel(Type\Timing::POST_PROCESSING, $timerInstance);
						
						$request = new PluralRead($this->frest, $requestParameters, NULL, $readSetting->getAlias());
						$request->setWasInternallyLoaded(TRUE);
						$request->setupWithResource($loadedResource);
						
						/** @var Result\PluralRead $result */
						$result = $request->generateResult();
	
						$this->frest->startTimingForLabel(Type\Timing::POST_PROCESSING, $timerInstance);
	
						$object->$alias = $result->getResourceObjects();
					}
				}
				else if ($readSetting instanceof Setting\SingularResourceRead) {
					/** @var Setting\SingularResourceRead $readSetting */
					
					$loadedResource = $this->getLoadedResource($readSetting->getResourceName());
	
					if (isset($this->partialSubReadSettings[$partialSubKey])) {
						$subReadSettings = $this->partialSubReadSettings[$partialSubKey];
						$this->addRequiredReadSettings($loadedResource, $subReadSettings);
					}
					else {
						$subReadSettings = $this->getLoadedResourceReadSettings($loadedResource, $readSetting);
					}
					
					$subObjects = array();
					// remove table-prefixed properties on object as they should be on a sub-object instead
					foreach ($objects as &$object) {
						if (!isset($object->$alias)) {
							$object->$alias = new \stdClass();
							$subObjects[] = &$object->$alias;
						}
						
						if (!isset($subReadSettings)) {
							continue;
						}
						
						foreach ($subReadSettings as $subReadSetting) {
							if ($subReadSetting instanceof Setting\FieldRead) {
								/** @var Setting\FieldRead $subReadSetting */
	
								$subAlias = $subReadSetting->getAlias();
								$subField = $loadedResource->getFieldForAlias($subAlias);
								$subTable = $loadedResource->getTableForField($subField);
								$subTableKey = isset($resourceAlias) ? "{$subTable}-{$resourceAlias}-{$alias}" : "{$subTable}-{$alias}";
								$subTableAbbrv = $this->getTableAbbreviation($subTableKey);
								$subProperty = "{$subTableAbbrv}_{$subAlias}";
	
								$subValue = $object->$subProperty;
	
								$object->$alias->$subAlias = $subValue;
								unset($object->$subProperty);
							}
							else if ($subReadSetting instanceof Setting\SingularResourceRead) { // move properties of object that should belong to subObject (only nests once? idk)
								$subLoadedResource = $this->getLoadedResource($subReadSetting->getResourceName());
	
								$subReadAlias = $subReadSetting->getAlias();
								$partialDeepKey = isset($resourceAlias) ? "{$resourceAlias}.{$alias}.{$subReadAlias}" : "{$alias}.{$subReadAlias}";
	
								if (isset($this->partialSubReadSettings[$partialDeepKey])) {
									$deepReadSettings = $this->partialSubReadSettings[$partialDeepKey];
									$this->addRequiredReadSettings($subLoadedResource, $deepReadSettings);
								}
								else {
									$deepReadSettings = $this->getLoadedResourceReadSettings($subLoadedResource, $subReadSetting);
								}
								
	
								foreach ($deepReadSettings as $deepReadSetting) {
									if ($deepReadSetting instanceof Setting\FieldRead) {
										$deepAlias = $deepReadSetting->getAlias();
										$deepField = $subLoadedResource->getFieldForAlias($deepAlias);
										$deepTable = $subLoadedResource->getTableForField($deepField);
										$deepTableKey = "{$deepTable}-{$alias}-{$subReadAlias}";
										$deepTableAbbrv = $this->getTableAbbreviation($deepTableKey);
										$deepProperty = "{$deepTableAbbrv}_{$deepAlias}";
	
										$deepValue = $object->$deepProperty;
	
										$object->$alias->$deepProperty = $deepValue;
										unset($object->$deepProperty);
									}
								}
							}
						}
					}
	
					// parse sub-objects as well
					$subAlias = $readSetting->getAlias();
					$subResourceAlias = isset($resourceAlias) ? "{$resourceAlias}.{$subAlias}" : $subAlias;
	
					$this->parseObjects($loadedResource, $subObjects, $subReadSettings, $subResourceAlias);
				}
				else if ($readSetting instanceof Setting\FieldRead) {		
					/** @var Setting\FieldRead $readSetting */
					
					$fieldSetting = $resource->getFieldSettingForAlias($alias);
					$variableType = $fieldSetting->getVariableType();
	
					foreach ($objects as &$object) {
						$value = Type\Variable::castValue($object->$alias, $variableType);
						
						$filterFunction = $readSetting->getFilterFunction();
						if (isset($filterFunction)) {
							if (!method_exists($resource, $filterFunction)) {
								throw new FREST\Exception(FREST\Exception::FilterFunctionMissing, "Function name: '{$filterFunction}', resource: '{$resource->getName()}'");
							}
							
							$value = $resource->$filterFunction($value);
						}
						
						$object->$alias = $value;
					}
				}
				
				$this->frest->stopTimingForLabel(Type\Timing::POST_PROCESSING, $timerInstance);
			}
		}

		$this->frest->startTimingForLabel(Type\Timing::POST_PROCESSING, $timerInstance);

		// use first object as reference to see what aliases have been set so far (used for computed aliases below)
		$oldObjects = reset($objects);
		$refObject = &$oldObjects;
		
		/*
		// generate a list of aliases that have so-far been set on each object (used for computed aliases)
		$aliasesDefinedOnObjects = array();
		$objectVars = get_object_vars($firstObject);
		foreach ($objectVars as $alias=>$value) {
			if (isset($value)) {
				$aliasesDefinedOnObjects[$alias] = $alias;
			}
		}
		*/
		
		// computed aliases
		$lastComputedReadSettingCount = count($computedReadSettings);
		
		$failedComputingSettings = FALSE;
		while ($lastComputedReadSettingCount > 0) {
			/** @var Setting\ComputedRead $computedReadSetting */
			foreach ($computedReadSettings as $computedReadSetting) {
				$alias = $computedReadSetting->getAlias();
				
				// determine if all aliases required for this computed alias have been defined 
				// (should only NOT be set if the required alias is also a computed column and
				//  hasn't been computed yet)
				$hasAllAliasesRequired = TRUE;
				$requiredAliases = $computedReadSetting->getRequiredAliases();
				foreach ($requiredAliases as $requiredAlias) {
					$requiredAliasFromPartial = $this->parsePartialAliasFromString($requiredAlias, $subAliases, FALSE);
					
					// TODO: error check required alias partial syntax
					
					if (!isset($refObject->$requiredAliasFromPartial)) {
						$hasAllAliasesRequired = FALSE;
						break;
					}

					if (isset($subAliases)) {
						foreach ($subAliases as $subAlias) {
							if (!isset($refObject->$requiredAliasFromPartial->$subAlias)) {
								$hasAllAliasesRequired = FALSE;
								break;
							}
						}
					}

					if (!$hasAllAliasesRequired) {
						break;
					}
				}

				// move on to the next computed alias (assuming there is one)
				// we'll compute this alias later once its required/computed alias
				// is determined
				if (!$hasAllAliasesRequired) {
					continue;
				}

				$function = $computedReadSetting->getFunction();

				// keep track of what aliases have been defined for use by other computed aliases
				unset($computedReadSettings[$alias]);
				
				foreach ($objects as &$object) {
					if (!method_exists($resource, $function)) {
						throw new FREST\Exception(FREST\Exception::ComputationFunctionMissing, "The Func '{$function}' is not defined in resource '{$resource->getName()}'");
					}
					
					$object->$alias = $resource->$function($object);
				}
			}

			// if we run through loop and no aliases have been computed, break out of loop and fail
			$currentComputedReadSettingCount = count($computedReadSettings);
			if ($currentComputedReadSettingCount >= $lastComputedReadSettingCount) {
				$failedComputingSettings = TRUE;
				break;
			}
			
			$lastComputedReadSettingCount = $currentComputedReadSettingCount;
		}
		
		if ($failedComputingSettings) {
			throw new FREST\Exception(FREST\Exception::Config, 'All computed aliases could not be computed. Check your config and make sure there are no conflicting required field Setting');
		}
		
		$resourceName = $resource->getName();

		if (isset($readSettings)) {
			foreach ($readSettings as $readSetting) {
				$alias = $readSetting->getAlias();

				// remove property if added only by requirement of other properties
				if (isset($this->requiredAliasesAdded[$resourceName][$alias])) {
					foreach ($objects as &$object) {
						unset($object->$alias);
					}
				}
			}
		}

		$this->frest->stopTimingForLabel(Type\Timing::POST_PROCESSING, $timerInstance);
	}


	/**
	 * @param string $partialKey
	 * @return array|NULL
	 */
	protected function generateNewFields($partialKey) {
		$fields = array();
		
		if (isset($this->partialSubReadSettings[$partialKey])) {
			$partialReadSettings = $this->partialSubReadSettings[$partialKey];

			/** @var Setting\Read $partialReadSetting */
			foreach ($partialReadSettings as $partialReadSetting) {
				$alias = $partialReadSetting->getAlias();
				
				$subPartialKey = "{$partialKey}.{$alias}";
				$subNewFields = $this->generateNewFields($subPartialKey);
				if (isset($subNewFields)) {
					$subNewFieldString = implode(',', $subNewFields);
					$fieldValue = "{$alias}({$subNewFieldString})";
				}
				else {
					$fieldValue = $alias;
				}
				
				$fields[] = $fieldValue;
			}
			
		}
		
		if (count($fields) > 0) {
			return $fields;
		}
		else {
			return NULL;
		}
	}


	/**
	 * @param string $resourceName
	 * @param Request\Read $request
	 *
	 * @return FREST\Resource
	 */
	protected function getLoadedResource($resourceName, $request = NULL) {
		if (!isset(self::$loadedResources[$resourceName])) {
			if (!isset($request)) {
				$request = $this;
			}

			$resource = $this->frest->loadResourceWithName($resourceName, $request);

			self::$loadedResources[$resourceName] = $resource;
		}

		return self::$loadedResources[$resourceName];
	}


	/**
	 * @param FREST\Resource $resource
	 * @param array $readSettings
	 * @return array|NULL
	 */
	protected function generateTableSpecs($resource, $readSettings) {
		$tableSpecs = array();

		/** @var Setting\Read $readSetting */
		foreach ($readSettings as $readSetting) {
			$alias = $readSetting->getAlias();
			$field = $resource->getFieldForAlias($alias);
			$table = $resource->getTableForField($field);

			if ($readSetting instanceof Setting\FieldRead || $readSetting instanceof Setting\SingularResourceRead) {
				$tableAbbvr = $this->getTableAbbreviation($table);
				$tableSpec = new Spec\Table(
					$table,
					$tableAbbvr
				);

				$tableSpecs[$table] = $tableSpec;
			}			
		}

		if (count($tableSpecs) > 0) {
			return $tableSpecs;
		}

		return NULL;
	}
	

	/**
	 * @param string $string
	 * @param array $subAliases
	 * @param bool $onlyAllowPartialSyntax
	 *
	 * @return string|NULL
	 */
	protected function parsePartialAliasFromString($string, &$subAliases = NULL, $onlyAllowPartialSyntax = TRUE) {
		$firstParenPos = strpos($string, '(');
		$lastParenPos = strrpos($string, ')');

		// check for partial syntax
		if ($firstParenPos !== FALSE && $lastParenPos !== FALSE && $lastParenPos == strlen($string) - 1 && $firstParenPos < $lastParenPos) {
			// split into alias and its partial field list
			$subAliasesString = trim(substr($string, $firstParenPos + 1, -1));
			$subAliases = $this->parseFieldParameterList($subAliasesString);
			$alias = substr($string, 0, $firstParenPos);
		}
		else {
			if ($onlyAllowPartialSyntax) {
				return NULL;
			}
			
			$alias = $string;
			$subAliases = NULL;
		}

		return $alias;
	}


	/**
	 * @param string $table
	 * @return string
	 */
	protected function getTableAbbreviation($table) {
		if (!isset($this->tableAbbreviations[$table])) {
			$abbreviationCount = count($this->tableAbbreviations);
			$abbreviation = 't'.$abbreviationCount;

			$this->tableAbbreviations[$table] = $abbreviation;
		}

		return $this->tableAbbreviations[$table];
	}
	
	/**
	 * @param FREST\Resource $loadedResource
	 * @param Setting\Read $responsibleReadSetting
	 *
	 * @return array
	 */
	protected function getLoadedResourceReadSettings($loadedResource, $responsibleReadSetting) {
		$resourceName = $loadedResource->getName();

		if (!isset($this->loadedResourceReadSettings[$resourceName])) {
			$parameters = array();

			if ($responsibleReadSetting instanceof Setting\SingularResourceRead) {
				$aliasesToRead = $responsibleReadSetting->getAliasesToRead();
				if (isset($aliasesToRead)) {
					$parameters['fields'] = implode(',', $aliasesToRead);
				}
			}
			else if ($responsibleReadSetting instanceof Setting\ComputedRead) {
				$requiredAliases = $responsibleReadSetting->getRequiredAliases();
				if (isset($requiredAliases)) {
					$parameters['fields'] = implode(',', $requiredAliases);
				}
			}

			$readSettings = $this->generateReadSettings($loadedResource, $parameters);

			$this->loadedResourceReadSettings[$resourceName] = $readSettings;
		}
		else {
			$readSettings = $this->loadedResourceReadSettings[$resourceName];
		}
		
		return $readSettings;
	}

	/**
	 * @param $paramterListString
	 * @return array
	 */
	protected function parseFieldParameterList($paramterListString) {
		$length = strlen($paramterListString);
		if ($length == 0) {
			return NULL;
		}
		
		$parenthesesDepth = 0;
		$paramStartIndex = 0;
		
		for ($i = 0; $i < $length; $i++) {
			$char = $paramterListString{$i};
			switch ($char) {
				case '(':
					$parenthesesDepth++;
					break;
				case ')':
					$parenthesesDepth--;
					break;
				case ',':
					if ($parenthesesDepth <= 0) {
						$parameterList[] = trim(substr($paramterListString, $paramStartIndex, $i - $paramStartIndex));
						$paramStartIndex = $i + 1;
					}
					break;
				default:
					break;
			}
		}
		$parameterList[] = trim(substr($paramterListString, $paramStartIndex, $i - $paramStartIndex));
		
		return $parameterList;
	}

	/**
	 * @param $parameter
	 * @param $value
	 * 
	 * @return bool
	 * @throws FREST\Exception
	 */
	protected function isValidURLParameter($parameter, $value) {
		/** @noinspection PhpUndefinedClassInspection */
		$isValid = parent::isValidURLParameter($parameter, $value);
		
		// if not already determined valid
		if (!$isValid) {
			if ($parameter == 'fields') {
				if (!$this->getWasInternallyLoaded()) {
					if (!$this->resource->getAllowFieldsParameter()) {
						throw new FREST\Exception(FREST\Exception::FieldsParameterNotAllowed);
					}

					if (!$this->resource->getAllowPartialSyntax() && (strpos($value, '(') !== FALSE || strpos($value, ')') !== FALSE)) {
						throw new FREST\Exception(FREST\Exception::PartialSyntaxNotAllowed);
					}
				}

				$isValid = TRUE;
			}
		}
		
		return $isValid;
	}
	
	/**
	 * @return FREST\Router
	 */
	public function getFREST() {
		return $this->frest;
	}

	/**
	 * @return array
	 */
	public static function getLoadedResources() {
		return self::$loadedResources;
	}

	/**
	 * @return array
	 */
	public function getPartialSubReadSettings() {
		return $this->partialSubReadSettings;
	}

	/**
	 * @return array
	 */
	public function getRequiredAliasesAdded() {
		return $this->requiredAliasesAdded;
	}

	/**
	 * @return array
	 */
	public function getTableAbbreviations() {
		return $this->tableAbbreviations;
	}

	/**
	 * @return array
	 */
	public function getReadSettings() {
		return $this->readSettings;
	}

	/**
	 * @return array
	 */
	public function getFieldSpecs() {
		return $this->fieldSpecs;
	}

	/**
	 * @return array
	 */
	public function getJoinSpecs() {
		return $this->joinSpecs;
	}

	/**
	 * @return array
	 */
	public function getTableSpecs() {
		return $this->tableSpecs;
	}
	
	
}