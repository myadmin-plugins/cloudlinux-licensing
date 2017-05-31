<?php
/**
 * Cloudlinux Functionality
 *
 * API Documentation at: .. ill fill this in later from forum posts
 *
 * Last Changed: $LastChangedDate: 2017-05-26 04:36:01 -0400 (Fri, 26 May 2017) $
 * @author detain
 * @version $Revision: 24803 $
 * @copyright 2017
 * @package MyAdmin
 * @category Licenses
 */

/**
 * returns a list of the cloudlinux licenses
 * @return bool|mixed
 */
function get_cloudlinux_licenses() {
	$cl = new Cloudlinux(CLOUDLINUX_LOGIN, CLOUDLINUX_KEY);
	$licenses = $cl->license_list();
	request_log('licenses', false, __FUNCTION__, 'cloudlinux', 'license_list', '', $licenses);
	return $licenses;
}

/**
 * deactivatges a cloudlinux licenes
 * @param string $ip ip address to deactivate
 * @param bool $type type of the lice4nse, can be 1 2 16 or leave blank/false for all types on that ip
 */
function deactivate_cloudlinux($ip, $type = false) {
	myadmin_log('cloudlinux', 'info', "Deactivate CloudLinux({$ip}, {$type}) called", __LINE__, __FILE__);
	$cl = new Cloudlinux(CLOUDLINUX_LOGIN, CLOUDLINUX_KEY);
	//myadmin_log('cloudlinux', 'info', json_encode($cl->xml_client), __LINE__, __FILE__);
	if ($type == false) {
		$response = $cl->remove($ip);
	} else {
		$response = $cl->remove($ip, $type);
	}
	request_log('licenses', false, __FUNCTION__, 'cloudlinux', 'remove_license', array($ip, $type), $response);
	myadmin_log('cloudlinux', 'info', "Deactivate CloudLinux({$ip}, {$type}) Resposne: " . json_encode($response), __LINE__, __FILE__);
}

/**
 * deactivates a kernelcare lciense
 * @param $ip ip address to deactivate
 */
function deactivate_kcare($ip) {
	deactivate_cloudlinux($ip, 16);
}
