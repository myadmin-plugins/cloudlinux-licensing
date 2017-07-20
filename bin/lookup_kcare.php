<?php
require_once (__DIR__.'/../../../include/functions.inc.php');
$url = "https://cln.cloudlinux.com/clweb/xmlrpc";
$license_type = 16;
///home/my/files/CloudLinux/php/clnreg.kcare-check.php
$cl_login = CLOUDLINUX_LOGIN;
$secret_key = CLOUDLINUX_KEY;
$ipAddress = $argv[1];
$now = time();
$sha1hash = sha1("$secret_key$now");
$auth_token = "$cl_login|$now|$sha1hash";

$BODY = "<?xml version=\"1.0\" encoding=\"UTF-8\"?> <methodCall> <methodName>registration.is_licensed</methodName> <params> <param> <value>$auth_token</value> </param> <param> <value>$ipAddress</value> </param> <param><value><boolean>1</boolean></value></param></params> </methodCall>";
$context = stream_context_create(
	[
	 'http' => [
			 'method' => 'POST',
			 'header' => 'Content-Type: application/xml; charset=UTF-8',
			 'content' => $BODY
	 ]
	]
);

$result = file_get_contents($url, FALSE, $context);
if (mb_strpos($result, '<i4>0</i4>')) {
	echo "Not Licensed\n";
} elseif (mb_strpos($result, '<i4>16</i4>')) {
	echo "KernelCare License\n";
} elseif (mb_strpos($result, '<i4>1</i4>')) {
	echo "License Type 1\n";
} elseif (mb_strpos($result, '<i4>2</i4>')) {
	echo "License Type 2\n";
}
