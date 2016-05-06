<?php
/**
 * Created by Brad Walker on 5/5/16 at 4:31 PM
*/

namespace FREST;


class Bootstrap 
{
	public static function strapOnTheBoots()
	{
		$resourceRequest = Resource\RequestConstructor::constructRequestFromGlobalState(); 
	}
} 