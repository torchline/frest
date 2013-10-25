<?php
/**
 * Created by Brad Walker on 8/14/13 at 12:58 PM
*/

namespace FREST\Spec;

/**
 * Class TableCreate
 * @package FREST\Spec
 */
class TableCreate {

	/** @var string */
	protected $table;
	
	/** @var array */
	protected $queryParameterSpecs;


	/**
	 * @param string $table
	 * @param array $createSpecs
	 */
	function __construct($table, $createSpecs) {
		$this->table = $table;
		
		$this->queryParameterSpecs = $this->generateQueryParameterSpecs($createSpecs);
	}


	/**
	 * @param array $createSpecs
	 * @return array
	 */
	private function generateQueryParameterSpecs($createSpecs) {
		$queryParameterSpecs = array();

		/** @var Create $createSpec */
		foreach ($createSpecs as $createSpec) {
			$alias = $createSpec->getAlias();

			$parameterName = ":{$alias}";

			$queryParameterSpec = new QueryParameter(
				$createSpec->getField(),
				$parameterName,
				$createSpec->getValue(),
				$createSpec->getVariableType()
			);

			$queryParameterSpecs[$alias] = $queryParameterSpec;
		}

		if (count($queryParameterSpecs) > 0) {
			return $queryParameterSpecs;
		}

		return NULL;
	}
	
	
	/**
	 * @return string
	 */
	public function getTable()
	{
		return $this->table;
	}

	/**
	 * @return array
	 */
	public function getQueryParameterSpecs()
	{
		return $this->queryParameterSpecs;
	}	
	
	
}