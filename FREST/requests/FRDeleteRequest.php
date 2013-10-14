<?php
/**
 * Created by Brad Walker on 6/4/13 at 5:11 PM
 */

require_once(dirname(__FILE__).'/FRRequest.php');
require_once(dirname(__FILE__).'/../specs/FRTableDeleteSpec.php');
require_once(dirname(__FILE__).'/../results/FRDeleteResult.php');


class FRDeleteRequest extends FRRequest {

	/** @var array */
	protected $tableDeleteSpecs;
	

	public function setupWithResource($resource, &$error = NULL) {
		parent::setupWithResource($resource, $error);
		if (isset($error)) {
			return;
		}

		if (!isset($this->resourceID)) {
			$error = new FRErrorResult(FRErrorResult::MissingResourceID, 400, '');
			return;
		}
		
		$this->tableDeleteSpecs = $this->generateTableDeleteSpecs($this->resource, $error);
		if (isset($error)) {
			return;
		}
	}

	public function generateResult($forceRegen = FALSE) {
		$this->frest->startTimingForLabel(FRTiming::PROCESSING, 'delete');

		$otherResult = parent::generateResult($forceRegen);
		if (isset($otherResult)) {
			return $otherResult;
		}

		$pdo = $this->frest->getConfig()->getPDO();

		/** @var FRFieldSetting $idFieldSetting */
		$this->resource->getIDField($idFieldSetting);
		
		$isPerformingTransaction = FALSE;

		if (count($this->tableDeleteSpecs) > 1) {
			$pdo->beginTransaction();
			$isPerformingTransaction = TRUE;
		}

		$this->frest->stopTimingForLabel(FRTiming::PROCESSING, 'delete');

		/** @var FRTableDeleteSpec $tableDeleteSpec */
		foreach ($this->tableDeleteSpecs as $tableDeleteSpec) {
			$table = $tableDeleteSpec->getTable();
			$idFieldName = $this->resource->getIDFieldForTable($table);

			$this->frest->startTimingForLabel(FRTiming::SQL, 'delete');

			$sql = "DELETE FROM {$table} WHERE {$idFieldName} = :_id";
			$deleteStmt = $pdo->prepare($sql);
			
			$deleteStmt->bindValue(
				':_id',
				$this->resourceID,
				FRVariableType::pdoTypeFromVariableType($idFieldSetting->getVariableType())
			);
			
			if (!$deleteStmt->execute()) {
				if ($isPerformingTransaction) {
					$pdo->rollBack();
				}
				
				$error = new FRErrorResult(FRErrorResult::SQLError, 500, 'Error deleting from database. '.implode(' ', $deleteStmt->errorInfo()));
				return $error;
			}

			$this->frest->stopTimingForLabel(FRTiming::SQL, 'delete');
		}

		$this->frest->startTimingForLabel(FRTiming::SQL, 'delete');

		if ($isPerformingTransaction) {
			$pdo->commit();
		}

		$this->frest->stopTimingForLabel(FRTiming::SQL, 'delete');

		$this->result = new FRDeleteResult();

		return $this->result;
	}


	/**
	 * @param FRResource $resource
	 * @param FRErrorResult $error
	 * @return array
	 */
	private function generateTableDeleteSpecs($resource, &$error = NULL) {
		$tableDeleteSpecs = array();

		$tableSettings = $resource->getTableSettings();

		/** @var FRTableSetting $tableSetting */
		foreach ($tableSettings as $tableSetting) {
			$tableDeleteSpec = new FRTableDeleteSpec($tableSetting->getTable(), $error);
			
			if (isset($error)) {
				return NULL;
			}
			
			$tableDeleteSpecs[] = $tableDeleteSpec;
		}

		if (count($tableDeleteSpecs) > 0) {
			return $tableDeleteSpecs;
		}

		return NULL;
	}
}