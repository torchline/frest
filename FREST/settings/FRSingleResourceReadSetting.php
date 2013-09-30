<?php
/**
 * Created by Brad Walker on 6/5/13 at 11:46 AM
*/

require_once(dirname(__FILE__).'/FRReadSetting.php');

class FRSingleResourceReadSetting extends FRReadSetting {
	
	/** @var string */
	protected $resourceName;
	
	/** @var string */
	protected $resourceJoinAlias;
	
	/** @var array */
	protected $aliasesToRead;


	/**
	 * @param string $alias
	 * @param string $resourceName
	 * @param string $resourceJoinAlias
	 * @param array $aliasesToRead
	 * @param bool $default
	 */
	public function __construct($alias, $resourceName, $resourceJoinAlias, $aliasesToRead = NULL, $default = FALSE) {
		$this->alias = $alias;
		$this->default = $default;
		
		$this->resourceName = $resourceName;
		$this->resourceJoinAlias = $resourceJoinAlias;
		$this->aliasesToRead = $aliasesToRead;
	}
	
	// TODO: aliasesToRead does not stay unique when having multiple instances of one table (e.g. two Users properties loaded will use aliasesToRead of first one defined in readSettings)
	

	/**
	 * @return string
	 */
	public function getResourceJoinAlias() {
		return $this->resourceJoinAlias;
	}

	/**
	 * @return string
	 */
	public function getResourceName() {
		return $this->resourceName;
	}

	/**
	 * @return array
	 */
	public function getAliasesToRead()
	{
		return $this->aliasesToRead;
	}
	
	
}