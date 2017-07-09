<?php
/**
 * Cloudlinux Functionality
 *
 * API Documentation at: .. ill fill this in later from forum posts
 *
 * Last Changed: $LastChangedDate: 2017-05-26 04:36:01 -0400 (Fri, 26 May 2017) $
 * @author detain
 * @copyright 2017
 * @package MyAdmin
 * @category Licenses
 */

use Detain\Cloudlinux\Cloudlinux; 

/**
 * returns a list of the cloudlinux licenses
 * @return bool|mixed
 */
function get_cloudlinux_licenses() {
	$cl = new Cloudlinux(CLOUDLINUX_LOGIN, CLOUDLINUX_KEY);
	$licenses = $cl->licenseList();
	request_log('licenses', FALSE, __FUNCTION__, 'cloudlinux', 'licenseList', '', $licenses);
	return $licenses;
}

/**
 * deactivatges a cloudlinux licenes
 * @param string $ipAddress ip address to deactivate
 * @param bool $type type of the lice4nse, can be 1 2 16 or leave blank/false for all types on that ip
 */
function deactivate_cloudlinux($ipAddress, $type = FALSE) {
	myadmin_log('cloudlinux', 'info', "Deactivate CloudLinux({$ipAddress}, {$type}) called", __LINE__, __FILE__);
	$cl = new Cloudlinux(CLOUDLINUX_LOGIN, CLOUDLINUX_KEY);
	//myadmin_log('cloudlinux', 'info', json_encode($cl->xml_client), __LINE__, __FILE__);
	if ($type == FALSE) {
		$response = $cl->remove($ipAddress);
	} else {
		$response = $cl->remove($ipAddress, $type);
	}
	request_log('licenses', FALSE, __FUNCTION__, 'cloudlinux', 'removeLicense', array($ipAddress, $type), $response);
	myadmin_log('cloudlinux', 'info', "Deactivate CloudLinux({$ipAddress}, {$type}) Resposne: ".json_encode($response), __LINE__, __FILE__);
}

/**
 * deactivates a kernelcare lciense
 * @param $ipAddress ip address to deactivate
 */
function deactivate_kcare($ipAddress) {
	deactivate_cloudlinux($ipAddress, 16);
}
