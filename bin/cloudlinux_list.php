<?php

use Detain\Cloudlinux\Cloudlinux;

ini_set('display_errors', 'on');
require_once __DIR__.'/../../../../include/functions.inc.php';
require_once __DIR__.'/../../cloudlinux-licensing/src/Cloudlinux.php';
$webpage = false;
define('VERBOSE_MODE', false);
global $console;

$cl = new Cloudlinux(CLOUDLINUX_LOGIN, CLOUDLINUX_KEY);
$types = $cl->licenses();
print_r($types);
