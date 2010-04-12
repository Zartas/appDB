<?php
/**
 * Appulous V2 API (alpha)
 * Kyek
 * April 29, 2009
 */
require_once(__DIR__ . '/../lib/appdb/appdb.inc.php');
use appdb\api\RequestProcessor;

$postname = "request";
if (!isset($_POST[$postname])) {
	header("HTTP/1.0 403 Forbidden");
	die('<h1>403 Forbidden</h1>');
}
$rp = new RequestProcessor();
$rp->handleErrors();
$res = $rp->processRequest($_POST[$postname]);
echo $res;
?>
