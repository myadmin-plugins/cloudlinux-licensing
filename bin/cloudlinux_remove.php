#!/usr/bin/env php
<?php
use Detain\Cloudlinux\Cloudlinux;
require_once __DIR__.'/../../../../include/functions.inc.php';

$cl = new Cloudlinux(CLOUDLINUX_LOGIN, CLOUDLINUX_KEY);
if (isset($_SERVER['argv'][2]))
	print_r($cl->remove($_SERVER['argv'][1], $_SERVER['argv'][2]));
else
	print_r($cl->remove($_SERVER['argv'][1]));
