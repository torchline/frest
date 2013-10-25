<?php
/**
 * Created by Brad Walker on 6/4/13 at 5:11 PM
 */

namespace FREST\Request;

use FREST;
use FREST\Enum;
use FREST\Result;
use FREST\Setting;
use FREST\Spec;

require_once(dirname(__FILE__) . '/Request.php');
require_once(dirname(__FILE__) . '/../Spec/TableDelete.php');
require_once(dirname(__FILE__) . '/../Result/Delete.php');

/**
 * Class Delete
 * @package FREST\Request
 */
class Delete extends Request {

	/** @var array */
	protected $tableDeleteSpecs;


	/**
	 * @param FREST\Resource $resource
	 * @throws FREST\Exception
	 */
	public function setupWithResource($resource) {
		parent::setupWithResource($resource);

		if (!isset($this->resourceID)) {
			throw new FREST\Exception(FREST\Exception::MissingResourceID);
		}
		
		$this->tableDeleteSpecs = $this->generateTableDeleteSpecs($this->resource);
	}

	/**
	 * @param bool $forceRegen
	 * @return Result\Delete
	 * @throws FREST\Exception
	 */
	public function generateResult($forceRegen = FALSE) {
		$this->frest->startTimingForLabel(Enum\Timing::PROCESSING, 'delete');

		$otherResult = parent::generateResult($forceRegen);
		if (isset($otherResult)) {
			return $otherResult;
		}

		$pdo = $this->frest->getConfig()->getPDO();

		/** @var Setting\Field $idFieldSetting */
		$this->resource->getIDField($idFieldSetting);
		
		$isPerformingTransaction = FALSE;

		if (count($this->tableDeleteSpecs) > 1) {
			$pdo->beginTransaction();
			$isPerformingTransaction = TRUE;
		}

		$this->frest->stopTimingForLabel(Enum\Timing::PROCESSING, 'delete');

		/** @var Spec\TableDelete $tableDeleteSpec */
		foreach ($this->tableDeleteSpecs as $tableDeleteSpec) {
			$table = $tableDeleteSpec->getTable();
			$idFieldName = $this->resource->getIDFieldForTable($table);

			$this->frest->startTimingForLabel(Enum\Timing::SQL, 'delete');

			$sql = "DELETE FROM {$table} WHERE {$idFieldName} = :_id";
			$deleteStmt = $pdo->prepare($sql);
			
			$deleteStmt->bindValue(
				':_id',
				$this->resourceID,
				Enum\VariableType::pdoTypeFromVariableType($idFieldSetting->getVariableType())
			);
			
			if (!$deleteStmt->execute()) {
				if ($isPerformingTransaction) {
					$pdo->rollBack();
				}
				throw new FREST\Exception(FREST\Exception::SQLError, 'Error deleting from database');
			}

			$this->frest->stopTimingForLabel(Enum\Timing::SQL, 'delete');
		}

		$this->frest->startTimingForLabel(Enum\Timing::SQL, 'delete');

		if ($isPerformingTransaction) {
			$pdo->commit();
		}

		$this->frest->stopTimingForLabel(Enum\Timing::SQL, 'delete');

		$this->result = new Result\Delete();

		return $this->result;
	}


	/**
	 * @param FREST\Resource $resource
	 * @return array
	 */
	private function generateTableDeleteSpecs($resource) {
		$tableDeleteSpecs = array();

		$tableSettings = $resource->getTableSettings();

		/** @var Setting\Table $tableSetting */
		foreach ($tableSettings as $tableSetting) {
			$tableDeleteSpec = new Spec\TableDelete($tableSetting->getTable());
			
			$tableDeleteSpecs[] = $tableDeleteSpec;
		}

		if (count($tableDeleteSpecs) > 0) {
			return $tableDeleteSpecs;
		}

		return NULL;
	}
}