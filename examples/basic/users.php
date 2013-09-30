<?php
/**
 * Created by Brad Walker on 9/29/13 at 11:20 PM
*/

require_once('../../FREST/FREST.php');

// some more raw hacks
$id = isset($_GET['__id']) ? $_GET['__id'] : NULL;
unset($_GET['__id']);

$config = new FRConfig(new PDO('mysql:dbname=frest;host=localhost', 'root'));
$frest = new FREST($config, NULL, $id);
$frest->outputResult();