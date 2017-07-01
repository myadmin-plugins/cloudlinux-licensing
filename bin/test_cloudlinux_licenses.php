<?php
use Detain\Cloudlinux\Cloudlinux; 
ini_set('display_errors', 'on');
require_once(__DIR__.'/../../../../include/functions.inc.php');
require_once(__DIR__.'/../../../include/licenses/Cloudlinux.php');
$webpage = FALSE;
define('VERBOSE_MODE', FALSE);
global $console;

$cl = new Cloudlinux(CLOUDLINUX_LOGIN, CLOUDLINUX_KEY);
$response = $cl->isLicensed('206.72.198.90', TRUE);
print_r($response);
if (!$response) {
	echo "Not Licensed\n";
	// $cl->license('66.45.228.100', 1);
	echo "License Created of type 1\n";
} else
	echo "Already Licensed\n";

echo "List of All Licnses:\n";
foreach ($cl->reconcile() as $license)
	echo $license['IP'].' is type '.$license['TYPE'].'. server registered in CLN with license: '.var_export($license['REGISTERED'], TRUE).PHP_EOL;
