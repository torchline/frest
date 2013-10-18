<?php
/**
 * Created by Brad Walker on 6/4/13 at 4:02 PM
*/

require_once(dirname(__FILE__).'/FRReadRequest.php');
require_once(dirname(__FILE__) . '/../results/FRSingularReadResult.php');

class FRSingularReadRequest extends FRReadRequest {
	
	public function setupWithResource($resource, &$error = NULL) {		
		parent::setupWithResource($resource, $error);
		if (isset($error)) {
			return $error;
		}

		// Resource ID
		if (isset($this->resourceID)) {
			/** @var FRFieldSetting $fieldSetting */
			$this->resource->getIDField($fieldSetting);
			$idType = $fieldSetting->getVariableType();

			$parsedResourceID = FRVariableType::castValue($this->resourceID, $idType);

			if (!isset($parsedResourceID)) {
				$typeString = FRVariableType::getString($idType);
				return new FRErrorResult(FRErrorResult::InvalidType, 400, "Resource ID needs to be of type '{$typeString}' but was supplied with '{$this->resourceID}'");
			}

			$this->resourceID = $parsedResourceID;
		}
	}
	
	public function generateResult($forceRegen = FALSE) {
		$this->frest->startTimingForLabel(FRTiming::PROCESSING, 'singularread');

		$otherResult = parent::generateResult($forceRegen);
		if (isset($otherResult)) {
			return $otherResult;
		}
		
		$fieldString = $this->generateFieldString($this->fieldSpecs, $error);
		if (isset($error)) {
			return $error;
		}
		
		$tablesToReadString = $this->generateTableString($this->tableSpecs, $error);
		if (isset($error)) {
			return $error;
		}

		$joinString = $this->generateJoinString($this->resource, $this->joinSpecs, $error);
		if (isset($error)) {
			return $error;
		}

		/** @var FRFieldSetting $idFieldSetting */
		$idField = $this->resource->getIDField($idFieldSetting);
		$tableWithID = $this->resource->getTableForField($idField);
		$tableAbbrvWithID = $this->getTableAbbreviation($tableWithID);

		$this->frest->stopTimingForLabel(FRTiming::PROCESSING, 'singularread');
		$this->frest->startTimingForLabel(FRTiming::SQL, 'singularread');

		$pdo = $this->frest->getConfig()->getPDO();

		$sql = "SELECT {$fieldString} FROM {$tablesToReadString}{$joinString} WHERE {$tableAbbrvWithID}.{$idField} = :id LIMIT 1";
		$stmt = $pdo->prepare($sql);
		$success = $stmt->execute(array(
			':id' => $this->resourceID
		));

		if (!$success) {
			return new FRErrorResult(FRErrorResult::SQLError, 500, implode(' - ', $stmt->errorInfo()));
		}

		$objects = $stmt->fetchAll(PDO::FETCH_OBJ);

		$resultsCount = count($objects);
		if ($resultsCount == 0) {
			return new FRErrorResult(FRErrorResult::NoResults, 404, '');
		}

		$this->frest->stopTimingForLabel(FRTiming::SQL, 'singularread');

		$this->parseObjects($this->resource, $objects, $this->readSettings, NULL, $error);
		if (isset($error)) {
			return $error;
		}
		
		$this->result = new FRSingularReadResult($objects[0]);

		return $this->result;
	}


	
	
	
}