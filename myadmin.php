<?php
/* TODO:
 - service type, category, and services  adding
 - dealing with the SERVICE_TYPES_cloudlinux define
 - add way to call/hook into install/uninstall
*/
return [
	'name' => 'Cloudlinux Licensing',
	'description' => 'Allows selling of Cloudlinux Server and VPS License Types.  More info at https://www.cloudlinux.com/',
	'help' => 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a cloudlinux license. Allow 10 minutes for activation.',
	'module' => 'licenses',
	'author' => 'detain@interserver.net',
	'home' => 'https://github.com/detain/myadmin-cloudlinux-licensing',
	'repo' => 'https://github.com/detain/myadmin-cloudlinux-licensing',
	'version' => '1.0.0',
	'type' => 'licenses',
	'hooks' => [
		'licenses.settings' => ['Detain\MyAdminCloudlinux\Plugin', 'Settings'],
		'licenses.activate' => ['Detain\MyAdminCloudlinux\Plugin', 'Activate'],
		'licenses.deactivate' => ['Detain\MyAdminCloudlinux\Plugin', 'Deactivate'],
		'licenses.change_ip' => ['Detain\MyAdminCloudlinux\Plugin', 'ChangeIp'],
		/* 'function.requirements' => ['Detain\MyAdminCloudlinux\Plugin', 'Requirements'],
		'ui.menu' => ['Detain\MyAdminCloudlinux\Plugin', 'Menu'] */
	],
];
