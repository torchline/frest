<?php
/**
 * Created by Brad Walker on 9/29/13 at 11:20 PM
*/

require_once('../FREST/FREST.php');

// some more raw hacks
if (isset($_GET['__id'])) {
	$id = $_GET['__id'];
	unset($_GET['__id']);

	FREST::outputSingle($id, NULL, array('fields' => 'id,name,token,modified,rank(id),inbox(id,text,sender(name))'));
}
else {
	FREST::outputMultiple();
}