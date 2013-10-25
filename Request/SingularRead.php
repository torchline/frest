<?php
/**
 * Created by Brad Walker on 6/4/13 at 4:02 PM
*/

namespace FREST\Request;

use FREST;
use FREST\Enum;
use FREST\Result;
use FREST\Setting;
use FREST\Spec;

require_once(dirname(__FILE__) . '/Read.php');
require_once(dirname(__FILE__) . '/../Result/SingularRead.php');

/**
 * Class SingularRead
 * @package FREST\Request
 */
class SingularRead extends Read {

	/**
	 * @param FREST\Resource $resource
	 * @throws FREST\Exception
	 */
	public function setupWithResource($resource) {
		parent::setupWithResource($resource);
		
		// Resource ID
		if (isset($this->resourceID)) {
			/** @var Setting\Field $fieldSetting */
			$this->resource->getIDField($fieldSetting);
			$idType = $fieldSetting->getVariableType();

			$parsedResourceID = Enum\VariableType::castValue($this->resourceID, $idType);

			if (!isset($parsedResourceID)) {
				$typeString = Enum\VariableType::getString($idType);
				throw new FREST\Exception(FREST\Exception::InvalidType, "Resource ID needs to be of type '{$typeString}' but was supplied with '{$this->resourceID}'");
			}

			$this->resourceID = $parsedResourceID;
		}
	}

	/**
	 * @param bool $forceRegen
	 * @return Result\SingularRead
	 * @throws FREST\Exception
	 */
	public function generateResult($forceRegen = FALSE) {
		$this->frest->startTimingForLabel(Enum\Timing::PROCESSING, 'singularread');

		$otherResult = parent::generateResult($forceRegen);
		if (isset($otherResult)) {
			return $otherResult;
		}
		
		$fieldString = $this->generateFieldString($this->fieldSpecs);
		$tablesToReadString = $this->generateTableString($this->tableSpecs);
		$joinString = $this->generateJoinString($this->resource, $this->joinSpecs);
		
		/** @var Setting\Field $idFieldSetting */
		$idField = $this->resource->getIDField($idFieldSetting);
		$tableWithID = $this->resource->getTableForField($idField);
		$tableAbbrvWithID = $this->getTableAbbreviation($tableWithID);

		$this->frest->stopTimingForLabel(Enum\Timing::PROCESSING, 'singularread');
		$this->frest->startTimingForLabel(Enum\Timing::SQL, 'singularread');

		$pdo = $this->frest->getConfig()->getPDO();

		$sql = "SELECT {$fieldString} FROM {$tablesToReadString}{$joinString} WHERE {$tableAbbrvWithID}.{$idField} = :id LIMIT 1";
		$stmt = $pdo->prepare($sql);
		$success = $stmt->execute(array(
			':id' => $this->resourceID
		));

		if (!$success) {
			throw new FREST\Exception(FREST\Exception::SQLError, 'Failed reading resource from database');
		}

		$objects = $stmt->fetchAll(\PDO::FETCH_OBJ);

		$resultsCount = count($objects);
		if ($resultsCount == 0) {
			throw new FREST\Exception(FREST\Exception::NoResults);
		}

		$this->frest->stopTimingForLabel(Enum\Timing::SQL, 'singularread');

		$this->parseObjects($this->resource, $objects, $this->readSettings);
		$this->result = new Result\SingularRead($objects[0]);

		return $this->result;
	}


	
	
	
}