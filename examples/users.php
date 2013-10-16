<?php
/**
 * Created by Brad Walker on 9/29/13 at 11:20 PM
*/

require_once('../FREST/FREST.php');

// some more raw hacks
if (isset($_GET['__id'])) {
	$id = $_GET['__id'];
	unset($_GET['__id']);

	FREST::outputSingle($id, NULL, array('fields' => 'id,name,token,modified,rank(id),inbox(sender(id,name,rank(name)),receiver(token,modified,rank(id,name)))')); // TODO: can't include sender AND receiver in inbox partial (even within resource)
}
else {
	FREST::outputMultiple();
}
