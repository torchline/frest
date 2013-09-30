<?php
/**
 * Created by Brad Walker on 8/14/13 at 12:58 PM
*/

class FRTableCreateSpec {

	/** @var string */
	protected $table;
	
	/** @var array */
	protected $queryParameterSpecs;


	/**
	 * @param string $table
	 * @param array $createSpecs
	 * @param FRErrorResult $error
	 */
	function __construct($table, $createSpecs, &$error = NULL) {
		$this->table = $table;
		
		$this->queryParameterSpecs = $this->generateQueryParameterSpecs($createSpecs, $error);
		if (isset($error)) {
			return;
		}
	}


	/**
	 * @param array $createSpecs
	 * @param FRErrorResult $error
	 * @return array
	 */
	private function generateQueryParameterSpecs($createSpecs, &$error = NULL) {
		$queryParameterSpecs = array();

		/** @var FRCreateSpec $createSpec */
		foreach ($createSpecs as $createSpec) {
			$alias = $createSpec->getAlias();

			$parameterName = ":{$alias}";

			$queryParameterSpec = new FRQueryParameterSpec(
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