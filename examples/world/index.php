<?php
/**
 * Created by Brad Walker on 10/17/13 at 1:50 AM
*/

require_once('../../FREST/FREST.php');

$parts = explode('?', $_SERVER['REQUEST_URI']);
$pathParts = explode('/', $parts[0]);
$resource = $pathParts[3];

FREST\FREST::all($resource)->outputResult();