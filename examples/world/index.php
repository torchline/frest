<?php
/**
 * Created by Brad Walker on 10/17/13 at 1:50 AM
*/

require_once('../../FREST/FREST.php');

$resource = explode('/', explode('?', $_SERVER['REQUEST_URI'])[0])[3];

FREST::all($resource)->outputResult();