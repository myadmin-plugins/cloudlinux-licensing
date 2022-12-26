#!/usr/bin/env php
<?php

require_once __DIR__.'/../../../../include/functions.inc.php';

function_requirements('get_cloudlinux_licenses');
print_r(get_cloudlinux_licenses());
