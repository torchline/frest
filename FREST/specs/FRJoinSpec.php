<?php
/**
 * Created by Brad Walker on 6/5/13 at 2:23 PM
*/


/**
 * Class FRJoinSpec
 */
class FRJoinSpec {
	
	/** @var string */
	protected $resourceAlias;
	
	/** @var string */
	protected $resourceName;
	
	/** @var string */
	protected $tableToJoin;

	/** @var string */
	protected $fieldToJoin;
	
	/** @var string */
	protected $field;
	
	/** @var string */
	protected $alias;

	/** @var string */
	protected $type;

	/** @var array */
	protected $subJoinSpecs = NULL;
	
	
	/**
	 * @param string $resourceAlias
	 * @param string $resourceName
	 * @param string $tableToJoin
	 * @param string $fieldToJoin
	 * @param string $field
	 * @param string $alias
	 * @param string $type
	 * @param array $subJoinSpecs
	 */
	public function __construct($resourceAlias, $resourceName, $tableToJoin, $fieldToJoin, $field, $alias, $type, $subJoinSpecs = NULL) {
		$this->resourceAlias = $resourceAlias;
		$this->resourceName = $resourceName;
		$this->tableToJoin = $tableToJoin;
		$this->fieldToJoin = $fieldToJoin;
		$this->field = $field;
		$this->alias = $alias;
		$this->type = $type;
		$this->subJoinSpecs = $subJoinSpecs;
	}
	
	

	/**
	 * @return string
	 */
	public function getResourceAlias() {
		return $this->resourceAlias;
	}
	
	/**
	 * @return string
	 */
	public function getResourceName() {
		return $this->resourceName;
	}

	/**
	 * @return string
	 */
	public function getTableToJoin() {
		return $this->tableToJoin;
	}

	/**
	 * @return string
	 */
	public function getFieldToJoin() {
		return $this->fieldToJoin;
	}

	/**
	 * @return string
	 */
	public function getField() {
		return $this->field;
	}

	/**
	 * @return string
	 */
	public function getAlias()
	{
		return $this->alias;
	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @return array
	 */
	public function getSubJoinSpecs() {
		return $this->subJoinSpecs;
	}


}