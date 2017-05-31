#!/usr/bin/php
<?php

require_once(__DIR__ . '/../../../include/functions.inc.php');

$cl = new Cloudlinux(CLOUDLINUX_LOGIN, CLOUDLINUX_KEY);
//print_r($cl->check($_SERVER['argv'][1]));
//print_r($cl->xml_is_licensed($_SERVER['argv'][1]));
print_r($cl->is_licensed($_SERVER['argv'][1]));
