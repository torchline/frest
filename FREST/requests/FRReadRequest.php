<?php
/**
 * Created by Brad Walker on 6/6/13 at 1:42 PM
*/

require_once(dirname(__FILE__).'/FRRequest.php');
require_once(dirname(__FILE__).'/../resources/FRResource.php');
require_once(dirname(__FILE__).'/../specs/FRJoinSpec.php');
require_once(dirname(__FILE__).'/../specs/FRFieldSpec.php');
require_once(dirname(__FILE__).'/../specs/FRTableSpec.php');

/**
 * Class FRReadRequest
 */
abstract class FRReadRequest extends FRRequest {
	
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
	
	
	public function __construct($frest, $resourceID = NULL, $parameters, $resourceFunctionName = NULL) {
		$this->miscParameters['fields'] = TRUE;
		
		parent::__construct($frest, $resourceID, $parameters, $resourceFunctionName);
	}
	
	public function setupWithResource($resource, &$error = NULL) {
		parent::setupWithResource($resource, $error);
		if (isset($error)) {
			return;
		}
		
		$this->readSettings = $this->generateReadSettings($this->resource, $this->parameters, NULL, $error);
		if (isset($error)) {
			return;
		}
		$class = get_class($this->resource);
		
		if (!isset($this->readSettings)) {
			$error = new FRErrorResult(FRErrorResult::Config, 500, "No read settings exist or none are default");
			return;
		}

		$this->joinSpecs = $this->generateJoinSpecs($this->resource, $this->readSettings, $error);
		if (isset($error)) {
			return;
		}

		$this->fieldSpecs = $this->generateFieldSpecs($this->resource, $this->readSettings, FALSE, NULL, $error);
		if (isset($error)) {
			return;
		}
		
		$this->tableSpecs = $this->generateTableSpecs($this->resource, $this->readSettings, $error);
		if (isset($error)) {
			return;
		}
	}

	/**
	 * @param FRResource $resource
	 * @param array $parameters
	 * @param string $partialPrefix
	 * @param FRErrorResult $error
	 *
	 * @return array|NULL
	 */
	protected function generateReadSettings($resource, $parameters, $partialPrefix = NULL, &$error = NULL) {
		$readSettings = array();

		$allReadSettings = $resource->getReadSettings();

		if (isset($parameters['fields'])) {
			$userSpecifiedAliases = $this->parseFieldParameterList($parameters['fields']);
			
			foreach ($userSpecifiedAliases as $alias) {
				$readSetting = isset($allReadSettings[$alias]) ? $allReadSettings[$alias] : NULL;

				if (isset($readSetting)) {
					$readSettings[$alias] = $readSetting;
				}
				else {
					// check for settings for partial object alias
					$aliasFromPartial = $this->parsePartialAliasFromString($alias, $definedSubAliases);
					
					if (!isset($definedSubAliases)) {
						$error = new FRErrorResult(FRErrorResult::InvalidField, 400, "No partial fields passed but partial syntax attempted in field '{$aliasFromPartial}'");
						return NULL;
					}
					
					if (!isset($aliasFromPartial)) {
						$error = new FRErrorResult(FRErrorResult::InvalidField, 400, "Invalid field name specified in 'fields' parameter: '{$alias}'");
						return NULL;
					}

					/** @var FRSingleResourceReadSetting $readSetting */
					$readSetting = $allReadSettings[$aliasFromPartial];

					if (!isset($readSetting)) {
						$error = new FRErrorResult(FRErrorResult::InvalidField, 400, "Invalid field name specified in 'fields' parameter: '{$aliasFromPartial}'");
						return NULL;
					}

					if (!($readSetting instanceof FRSingleResourceReadSetting) && !($readSetting instanceof FRMultiResourceReadSetting)) {
						$error = new FRErrorResult(FRErrorResult::PartialSyntaxNotSupported, 400, "The field '{$aliasFromPartial}' does not respond to partial object syntax");
						return NULL;
					}

					// load external resource referenced by this resource
					$loadedResource = $this->getLoadedResource($readSetting->getResourceName(), $this, $error);
					if (isset($error)) {
						return NULL;
					}

					$allLoadedResourceReadSettings = $this->getLoadedResourceReadSettings($loadedResource, $readSetting, $error);
					if (isset($error)) {
						return NULL;
					}

					$loadedResourceReadSettings = array();

					// check if the referenced resource has all partial fields specified
					foreach ($definedSubAliases as $subAlias) {
						if (!isset($allLoadedResourceReadSettings[$subAlias])) {
							$subAliasFromPartial = $this->parsePartialAliasFromString($subAlias, $deepAliases);

							if (!isset($allLoadedResourceReadSettings[$subAliasFromPartial])) {
								$error = new FRErrorResult(FRErrorResult::InvalidField, 400, "Invalid sub-field '{$subAlias}' specified in field '{$alias}'");
								return NULL;
							}

							/** @var FRReadSetting $subreadSetting */
							$subReadSetting = $allLoadedResourceReadSettings[$subAliasFromPartial];
							
							if ($subReadSetting instanceof FRSingleResourceReadSetting) {
								$subLoadedResource = $this->getLoadedResource($subReadSetting->getResourceName(), NULL, $error);
								if (isset($error)) {
									return NULL;
								}

								$subPartialPrefix = isset($partialPrefix) ? "{$partialPrefix}.{$aliasFromPartial}.{$subAliasFromPartial}" : "{$aliasFromPartial}.{$subAliasFromPartial}";

								$subReadSettings = $this->generateReadSettings($subLoadedResource, array('fields' => implode($deepAliases)), $subPartialPrefix, $error);
								if (isset($error)) {
									return NULL;
								}

								$this->partialSubReadSettings[$subPartialPrefix] = $subReadSettings;
							}
							else if ($subReadSetting instanceof FRMultiResourceReadSetting) {
								die("Some multi resource partial syntaxing has not yet been implemented. Sorry!");
							}
							else {
								$error = new FRErrorResult(FRErrorResult::PartialSyntaxNotSupported, 400, "The field '{$subAliasFromPartial}' does not support partial syntax");
								return NULL;
							}
							
							$subAlias = $subAliasFromPartial;
						}

						$loadedResourceReadSettings[$subAlias] = $allLoadedResourceReadSettings[$subAlias];
					}

					$readSettings[$aliasFromPartial] = $readSetting;
					
					$loadedPartialKey = isset($partialPrefix) ? "{$partialPrefix}.{$aliasFromPartial}" : $aliasFromPartial;
					$this->partialSubReadSettings[$loadedPartialKey] = $loadedResourceReadSettings;
				}
			}
		}
		else {
			// gather all aliases set to be read by default if none are specified by the client
			/** @var FRReadSetting $readSetting */
			foreach ($allReadSettings as $alias=>$readSetting) {
				if ($readSetting->getDefault()) {
					$readSettings[$alias] = $readSetting;
				}
			}
		}
		
		if (count($readSettings) > 0) {
			$this->addRequiredReadSettings($resource, $readSettings, $error);
			if (isset($error)) {
				return NULL;
			}
			
			return $readSettings;
		}

		return NULL;
	}

	/**
	 * @param FRResource $resource
	 * @param array $readSettings
	 * @param FRErrorResult $error
	 */
	private function addRequiredReadSettings($resource, &$readSettings, &$error = NULL) {
		$requiredReadSettings = array();

		$allReadSettings = $resource->getReadSettings();
		$class = get_class($resource);
		
		// find any aliases necessary for calculation of a computed alias that are not already defined in the read settings
		foreach ($readSettings as $readSetting) {
			if ($readSetting instanceof FRComputedReadSetting) {
				/** @var FRComputedReadSetting $readSetting */
				
				$requiredAliases = $readSetting->getRequiredAliases();
				
				foreach ($requiredAliases as $requiredAlias) {
					$requiredAliasFromPartial = $this->parsePartialAliasFromString($requiredAlias, $subAliases, FALSE);
					
					// TODO: error check required alias partial
					
					if (!isset($readSettings[$requiredAliasFromPartial]) && !isset($requiredReadSettings[$requiredAliasFromPartial])) {
						$requiredReadSetting = $allReadSettings[$requiredAliasFromPartial];
						
						$requiredReadSettings[$requiredAliasFromPartial] = $requiredReadSetting;
						
						$this->requiredAliasesAdded[$class][$requiredAliasFromPartial] = $requiredAlias;
						
						if (isset($subAliases)) {
							if (!($requiredReadSetting instanceof FRSingleResourceReadSetting)) {
								$error = new FRErrorResult(FRErrorResult::Config, 500, "The required alias '{$requiredAliasFromPartial}' is not a resource and should contain partial object syntax");
								return;
							}

							$loadedResource = $this->getLoadedResource($requiredReadSetting->getResourceName(), NULL, $error);

							$loadedResourceReadSettings = $this->getLoadedResourceReadSettings($loadedResource, $readSetting, $error);
							if (isset($error)) {
								return;
							}
							
							// find read settings for partial syntax that were defined in required alias config
							$subReadSettings = array();
							foreach ($subAliases as $subAlias) {
								$subReadSettings[$subAlias] = $loadedResourceReadSettings[$subAlias];
							}

							$this->partialSubReadSettings[$requiredAliasFromPartial] = $subReadSettings;
						}
					}
				}
			}
		}

		if (count($requiredReadSettings) > 0) {
			$readSettings = array_merge($readSettings, $requiredReadSettings);

			$this->addRequiredReadSettings($resource, $readSettings, $error);
			if (isset($error)) {
				return;
			}
		}
	}

	/**
	 * @param FRResource $resource
	 * @param array $readSettings
	 * @param bool $prefixWithTableAbbrv
	 * @param string $superAlias
	 * @param FRErrorResult $error
	 *
	 * @return array|NULL
	 */
	protected function generateFieldSpecs($resource, $readSettings, $prefixWithTableAbbrv = FALSE, $superAlias = NULL, &$error = NULL) {
		$fieldSpecs = array();

		foreach ($readSettings as $readSetting) {
			if ($readSetting instanceof FRSingleResourceReadSetting) {
				$loadedResource = $this->getLoadedResource($readSetting->getResourceName(), $this, $error);
				if (isset($error)) {
					return NULL;
				}

				$alias = $readSetting->getAlias();
				$partialKey = isset($superAlias) ? "{$superAlias}.{$alias}" : $alias;
				$subReadSettings = isset($this->partialSubReadSettings[$partialKey]) ? $this->partialSubReadSettings[$partialKey] : $this->getLoadedResourceReadSettings($loadedResource, $readSetting, $error);
				if (isset($error)) {
					return NULL;
				}
				
				$subFieldSpecs = $this->generateFieldSpecs($loadedResource, $subReadSettings, TRUE, $alias, $error);
				if (isset($error)) {
					return NULL;
				}

				$fieldSpecs = array_merge($fieldSpecs, $subFieldSpecs);
			}
			else if ($readSetting instanceof FRFieldReadSetting) {
				$alias = $readSetting->getAlias();
				$field = $resource->getFieldForAlias($alias);
				$table = $resource->getTableForField($field);

				if (!isset($superAlias)) {
					$tableAbbrv = $this->getTableAbbreviation($table);
				}
				else { // must be a joined resource
					//$tableAbbrv = $this->getTableAbbreviationForAlias($superAlias);
					$tableAbbrv = $this->getTableAbbreviation($table);
				}
				
				if ($prefixWithTableAbbrv) {
					$name = "{$tableAbbrv}_{$alias}";
				}
				else {
					$name = $alias;
				}

				$fieldSpec = new FRFieldSpec(
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
	 * @param FRErrorResult $error
	 *
	 * @return string|NULL
	 */
	protected function generateFieldString($fieldSpecs, &$error = NULL) {
		if (!isset($fieldSpecs)) {
			return NULL;
		}
		
		$fields = array();

		/** @var FRFieldSpec $fieldSpec */
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
	 * @param FRResource $resource
	 * @param array $readSettings
	 * @param FRErrorResult $error
	 *
	 * @return array|null
	 */
	protected function generateJoinSpecs($resource, $readSettings, &$error = NULL) {
		$joinSpecs = array();

		/** @var FRSingleResourceReadSetting $readSetting */
		foreach ($readSettings as $alias=>$readSetting) {
			if ($readSetting instanceof FRSingleResourceReadSetting) {
				$loadedResource = $this->getLoadedResource($readSetting->getResourceName(), $this, $error);
				if (isset($error)) {
					return NULL;
				}

				$loadedResourceJoinField = $loadedResource->getFieldForAlias($readSetting->getResourceJoinAlias());
				if (!isset($loadedResourceJoinField)) {
					$error = new FRErrorResult(FRErrorResult::Config, 500, "Field not found in resource '{$readSetting->getResourceName()}' for alias '{$readSetting->getResourceJoinAlias()}'");
					return NULL;
				}
				
				$loadedResourceTable = $loadedResource->getTableForField($loadedResourceJoinField);
				if (!isset($loadedResourceTable)) {
					$error = new FRErrorResult(FRErrorResult::Config, 500, "Table not found in resource '{$readSetting->getResourceName()}' for alias '{$readSetting->getResourceJoinAlias()}'");
					return NULL;
				}

				/*
				if (isset($readSetting[Setting::JOIN_DIRECTION])) {
					$joinType = strtoupper($readSetting[Setting::JOIN_DIRECTION]);

					if (strcasecmp($joinType, 'LEFT') != 0 && strcasecmp($joinType, 'INNER') != 0 && strcasecmp($joinType, 'RIGHT') != 0) {
						$error = new Error(Error::APIConfig, "joinDirection for '{$alias}' must be LEFT, INNER, or RIGHT");
						$this->error(500, $error->getCode(), self::ErrorMessageCommunication, $error->getDescription(TRUE));
					}
				}
				else {
					$joinType = 'INNER';
				}
				*/

				$joinType = 'INNER';
				$subReadSettings = isset($this->partialSubReadSettings[$alias]) ? $this->partialSubReadSettings[$alias] : $this->getLoadedResourceReadSettings($loadedResource, $readSetting, $error);
				if (isset($error)) {
					return NULL;
				}
				
				$subJoinSpecs = $this->generateJoinSpecs($loadedResource, $subReadSettings, $error);
				if (isset($error)) {
					return NULL;
				}
				
				$joinSpec = new FRJoinSpec(
					$readSetting->getResourceName(),
					$loadedResourceTable,
					$loadedResourceJoinField,
					$resource->getFieldForAlias($alias),
					$alias,
					$joinType,
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
	 * @param FRResource $resource
	 * @param array $joinSpecs
	 * @param FRErrorResult $error
	 *
	 * @return string
	 */
	protected function generateJoinString($resource, $joinSpecs, &$error = NULL) {
		if (!isset($joinSpecs)) {
			return '';
		}

		$joinsString = '';

		/** @var FRJoinSpec $joinSpec */
		foreach ($joinSpecs as $joinSpec) {
			$joinType = $joinSpec->getType();
			$joinTable = $joinSpec->getTableToJoin();
			//$joinTableAbbrv = $this->getTableAbbreviationForAlias($joinSpec->getAlias());
			$joinTableAbbrv = $this->getTableAbbreviation($joinTable);
			$field = $joinSpec->getField();
			$fieldTable = $resource->getTableForField($field);
			$tableAbbrv = $this->getTableAbbreviation($fieldTable);
			$joinField = $joinSpec->getFieldToJoin();

			$class = get_class($this->resource);
			$otherClass = get_class($resource);
			$joinsString .= " {$joinType} JOIN {$joinTable} {$joinTableAbbrv} ON {$tableAbbrv}.{$field} = {$joinTableAbbrv}.{$joinField}";

			$loadedResource = $this->getLoadedResource($joinSpec->getResourceName(), $this, $error);
			if (isset($error)) {
				return NULL;
			}

			$subJoinSpecs = $joinSpec->getSubJoinSpecs();

			if (isset($subJoinSpecs)) {
				$joinsString .= $this->generateJoinString($loadedResource, $subJoinSpecs, $error);
				if (isset($error)) {
					return NULL;
				}
			}
		}

		return $joinsString;
	}

	/**
	 * @param array $tableSpecs
	 * @param FRErrorResult $error
	 *
	 * @return string
	 */
	protected function generateTableString($tableSpecs, &$error = NULL) {
		if (!isset($tableSpecs)) {
			return NULL;
		}
		
		$i = 0;
		$tablesToReadJoinString = '';

		$tableSettings = $this->resource->getTableSettings();

		/** @var FRTableSpec $tableSpec */
		foreach ($tableSpecs as $tableSpec) {
			$table = $tableSpec->getTable();			
			$tableAbbrv = $tableSpec->getTableAbbreviation();

			if ($i > 0) {
				$onString = '';
				$finished = FALSE;
				$k = 0;

				/** @var FRTableSetting $tableSetting */
				foreach ($tableSettings as $tableSetting) {
					$tableAbbrvForIDField = $this->getTableAbbreviation($tableSetting->getTable());

					/** @var FRFieldSetting $firstFieldSetting */
					$fieldSettings = $tableSetting->getFieldSettings();
					$firstFieldSetting = $fieldSettings[0];
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
					$error = new FRErrorResult(FRErrorResult::Config, 500, "Could not find correct table-idField combinations to join");
					return NULL;
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
	 * @param FRResource $resource
	 * @param array $objects
	 * @param array $readSettings
	 * @param string $parentAlias
	 * @param FRErrorResult $error
	 */
	protected function parseObjects($resource, &$objects, $readSettings, $parentAlias = NULL, &$error = NULL) {				
		// stores the read settings that are just an FRComputedReadSetting
		$computedReadSettings = array();
		
		$timerInstance = isset($parentAlias) ? $parentAlias.'-parse' : 'parse';

		/** @var FRReadSetting $readSetting */
		foreach ($readSettings as $readSetting) {
			$this->frest->startTimingForLabel(FRTiming::POST_PROCESSING, $timerInstance);

			$alias = $readSetting->getAlias();
			$partialSubKey = isset($parentAlias) ? "{$parentAlias}.{$alias}" : $alias;

			if ($readSetting instanceof FRComputedReadSetting) {
				$computedReadSettings[$alias] = $readSetting;
			}
			else if ($readSetting instanceof FRMultiResourceReadSetting) {
				/** @var FRMultiResourceReadSetting $readSetting */
				
				$parameters = $readSetting->getParameters();
				
				// overwrite 'fields' parameter if partial syntax found
				$newFields = $this->generateNewFields($partialSubKey);
				if (isset($newFields)) {
					$parameters['fields'] = implode(',', $newFields);
				}
				
				$requiredAliases = $readSetting->getRequiredAliases();

				$loadedResource = $this->getLoadedResource($readSetting->getResourceName(), NULL, $error);
				if (isset($error)) {
					return;
				}

				foreach ($objects as &$object) {
					$requestParameters = array();
					foreach ($parameters as $field=>$parameter) {
						if (isset($requiredAliases[$field])) {
							$requiredAlias = $requiredAliases[$field];
							$requiredAliasValuePlaceholder = $loadedResource->value($requiredAlias);
							$parameter = str_replace($requiredAliasValuePlaceholder, $object->$requiredAlias, $parameter);
						}

						$requestParameters[$field] = $parameter;
					}

					$this->frest->stopTimingForLabel(FRTiming::POST_PROCESSING, $timerInstance);

					$request = new FRMultiReadRequest($this->frest, $requestParameters);
					$request->setupWithResource($loadedResource, $error);
					if (isset($error)) {
						return;
					}
					
					/** @var FRMultiReadResult $result */
					$result = $request->generateResult();
					if ($result instanceof FRErrorResult) {						
						$error = $result;
						return;
					}

					$this->frest->startTimingForLabel(FRTiming::POST_PROCESSING, $timerInstance);

					$object->$alias = $result->getResourceObjects();
				}
			}
			else if ($readSetting instanceof FRSingleResourceReadSetting) {
				/** @var FRSingleResourceReadSetting $readSetting */
								
				$loadedResource = $this->getLoadedResource($readSetting->getResourceName(), NULL, $error);
				if (isset($error)) {
					return;
				}

				$subReadSettings = isset($this->partialSubReadSettings[$partialSubKey]) ? $this->partialSubReadSettings[$partialSubKey] : $this->getLoadedResourceReadSettings($loadedResource, $readSetting, $error);
				if (isset($error)) {
					return;
				}

				$subObjects = array();
				// remove table-prefixed properties on object as they should be on a sub-object instead
				foreach ($objects as &$object) {
					if (!isset($object->$alias)) {
						$object->$alias = new stdClass();
						$subObjects[] = &$object->$alias;
					}
					
					foreach ($subReadSettings as $subReadSetting) {
						if ($subReadSetting instanceof FRFieldReadSetting) {
							/** @var FRFieldReadSetting $subReadSetting */

							$subAlias = $subReadSetting->getAlias();
							$subField = $loadedResource->getFieldForAlias($subAlias);
							$subTable = $loadedResource->getTableForField($subField);
							$subTableAbbrv = $this->getTableAbbreviation($subTable);
							$subProperty = "{$subTableAbbrv}_{$subAlias}";

							$subValue = $object->$subProperty;

							$object->$alias->$subAlias = $subValue;							
							unset($object->$subProperty);
						}
						else if ($subReadSetting instanceof FRSingleResourceReadSetting) { // move properties of object that should belong to subObject (only nests once? idk)
							$subLoadedResource = $this->getLoadedResource($subReadSetting->getResourceName(), NULL, $error);
							if (isset($error)) {
								return;
							}

							$subReadAlias = $subReadSetting->getAlias();
							$partialDeepKey = isset($parentAlias) ? "{$parentAlias}.{$alias}.{$subReadAlias}" : "{$alias}.{$subReadAlias}";

							$deepReadSettings = isset($this->partialSubReadSettings[$partialDeepKey]) ? $this->partialSubReadSettings[$partialDeepKey] : $this->getLoadedResourceReadSettings($subLoadedResource, $subReadSetting, $error);
							if (isset($error)) {
								return;
							}

							foreach ($deepReadSettings as $deepReadSetting) {
								if ($deepReadSetting instanceof FRFieldReadSetting) {
									$deepAlias = $deepReadSetting->getAlias();
									$deepField = $subLoadedResource->getFieldForAlias($deepAlias);
									$deepTable = $subLoadedResource->getTableForField($deepField);
									$deepTableAbbrv = $this->getTableAbbreviation($deepTable);
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
				$subParentAlias = isset($parentAlias) ? "{$parentAlias}.{$subAlias}" : $subAlias;

				$this->parseObjects($loadedResource, $subObjects, $subReadSettings, $subParentAlias, $error);
				
				if (isset($error)) {
					return;
				}
			}
			else if ($readSetting instanceof FRFieldReadSetting) {
				/** @var FRFieldReadSetting $readSetting */
				
				$fieldSetting = $resource->getFieldSettingForAlias($alias);
				$variableType = $fieldSetting->getVariableType();

				foreach ($objects as &$object) {
					$value = FRVariableType::castValue($object->$alias, $variableType);
					
					$filterFunction = $readSetting->getFilterFunction();
					if (isset($filterFunction)) {
						if (!method_exists($resource, $filterFunction)) {
							$resourceClassName = get_class($resource);
							$error = new FRErrorResult(FRErrorResult::FilterFunctionMissing, 500, "Function name: '{$filterFunction}', resource: '{$resourceClassName}'");
							return;
						}
						
						$value = $resource->$filterFunction($value);
					}
					
					$object->$alias = $value;
				}
			}
			
			$this->frest->stopTimingForLabel(FRTiming::POST_PROCESSING, $timerInstance);
		}

		$this->frest->startTimingForLabel(FRTiming::POST_PROCESSING, $timerInstance);
		
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
			/** @var FRComputedReadSetting $computedReadSetting */
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
						$resourceName = get_class($resource);
						$error = new FRErrorResult(FRErrorResult::ComputationFunctionMissing, 500, "The function '{$function}' is not defined in resource '{$resourceName}'");
						return;
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
			$error = new FRErrorResult(FRErrorResult::Config, 500, 'All computed aliases could not be computed. Check your config and make sure there are no conflicting required field settings');
			return;
		}
		
		$class = get_class($resource);

		foreach ($readSettings as $readSetting) {
			$alias = $readSetting->getAlias();

			// remove property if added only by requirement of other properties
			if (isset($this->requiredAliasesAdded[$class][$alias])) {
				foreach ($objects as &$object) {
					unset($object->$alias);
				}
			}
		}

		$this->frest->stopTimingForLabel(FRTiming::POST_PROCESSING, $timerInstance);
	}

	
	

	
	protected function generateNewFields($partialKey) {
		$fields = array();
		
		if (isset($this->partialSubReadSettings[$partialKey])) {
			$partialReadSettings = $this->partialSubReadSettings[$partialKey];

			/** @var FRReadSetting $partialReadSetting */
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
	 * @param FRReadRequest $request
	 * @param FRErrorResult $error
	 *
	 * @return FRResource
	 */
	protected function getLoadedResource($resourceName, $request = NULL, $error = NULL) {
		if (!isset(self::$loadedResources[$resourceName])) {
			if (!isset($request)) {
				$request = $this;
			}

			$resource = $this->frest->loadResourceWithName($resourceName, $request, $error);
			if (isset($error)) {
				return NULL;
			}

			self::$loadedResources[$resourceName] = $resource;
		}

		return self::$loadedResources[$resourceName];
	}


	/**
	 * @param FRResource $resource
	 * @param array $readSettings
	 * @param FRErrorResult $error
	 * @return array|NULL
	 */
	protected function generateTableSpecs($resource, $readSettings, &$error = NULL) {
		$tableSpecs = array();

		/** @var FRReadSetting $readSetting */
		foreach ($readSettings as $readSetting) {
			$alias = $readSetting->getAlias();
			$field = $resource->getFieldForAlias($alias);
			$table = $resource->getTableForField($field);

			if ($readSetting instanceof FRFieldReadSetting) {
				$tableSpec = new FRTableSpec(
					$table,
					$this->getTableAbbreviation($table)
				);

				$tableSpecs[$table] = $tableSpec;
			}
			else if ($readSetting instanceof FRSingleResourceReadSetting) {
				$tableSpec = new FRTableSpec(
					$table,
					$this->getTableAbbreviation($table)
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
	 * @param FRResource $loadedResource
	 * @param FRReadSetting $responsibleReadSetting
	 * @param FRErrorResult $error
	 * @return array
	 */
	protected function getLoadedResourceReadSettings($loadedResource, $responsibleReadSetting, &$error = NULL) {
		$resourceName = get_class($loadedResource);
		
		if (!isset($this->loadedResourceReadSettings[$resourceName])) {
			$parameters = array();
			
			if ($responsibleReadSetting instanceof FRSingleResourceReadSetting) {
				$aliasesToRead = $responsibleReadSetting->getAliasesToRead();
				if (isset($aliasesToRead)) {
					$parameters['fields'] = implode(',', $aliasesToRead);
				}
			}
			else if ($responsibleReadSetting instanceof FRComputedReadSetting) {
				$requiredAliases = $responsibleReadSetting->getRequiredAliases();
				if (isset($requiredAliases)) {
					$parameters['fields'] = implode(',', $requiredAliases);
				}
			}
			$readSettings = $this->generateReadSettings(
				$loadedResource,
				$parameters, 
				NULL,
				$error
			);
			
			if (isset($error)) {
				return NULL;
			}
			
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
						$parameterList[] = substr($paramterListString, $paramStartIndex, $i - $paramStartIndex);
						$paramStartIndex = $i + 1;
					}
					break;
				default:
					break;
			}
		}
		$parameterList[] = substr($paramterListString, $paramStartIndex, $i - $paramStartIndex);
		
		return $parameterList;
	}
	
	/**
	 * @return \FREST
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