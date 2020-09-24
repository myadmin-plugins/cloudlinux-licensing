<?php
/**
 * Cloudlinux Functionality
 *
 * API Documentation at: .. ill fill this in later from forum posts
 *
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2019
 * @package MyAdmin
 * @category Licenses
 */

use Detain\Cloudlinux\Cloudlinux;

/**
 * returns a list of the cloudlinux licenses
 *
 * @return bool|mixed
 * @throws \Detain\Cloudlinux\XmlRpcException
 */
function get_cloudlinux_licenses()
{
	$cl = new Cloudlinux(CLOUDLINUX_LOGIN, CLOUDLINUX_KEY);
	$licenses = $cl->licenseList();
	request_log('licenses', false, __FUNCTION__, 'cloudlinux', 'licenseList', '', $licenses);
	return $licenses;
}

/**
 * deactivatges a cloudlinux licenes
 * @param string $ipAddress ip address to deactivate
 * @param bool $type type of the lice4nse, can be 1 2 16 or leave blank/false for all types on that ip
 */
function deactivate_cloudlinux($ipAddress, $type = false)
{
	myadmin_log('cloudlinux', 'info', "Deactivate CloudLinux({$ipAddress}, {$type}) called", __LINE__, __FILE__);
	$cl = new Cloudlinux(CLOUDLINUX_LOGIN, CLOUDLINUX_KEY);
	//myadmin_log('cloudlinux', 'info', json_encode($cl->xml_client), __LINE__, __FILE__);
	if ($type == false) {
		$response = $cl->remove($ipAddress);
	} else {
		$response = $cl->remove($ipAddress, $type);
	}
	if (!isset($response['success']) || $response['success'] !== true) {
		$bodyRows = [];
		$bodyRows[] = 'License IP: '.$ipAddress.' unable to deactivate.';
		$bodyRows[] = 'Deactivation Response: .'.json_encode($response);
		$subject = 'Cloudlinux License Deactivation Issue IP: '.$ipAddress;
		$smartyE = new TFSmarty;
		$smartyE->assign('h1', 'Cloudlinux License Deactivation');
		$smartyE->assign('body_rows', $bodyRows);
		$msg = $smartyE->fetch('email/client/client_email.tpl');
		(new \MyAdmin\Mail())->multiMail($subject, $msg, ADMIN_EMAIL, 'client/client_email.tpl');
	}
	request_log('licenses', false, __FUNCTION__, 'cloudlinux', 'removeLicense', [$ipAddress, $type], $response);
	myadmin_log('cloudlinux', 'info', "Deactivate CloudLinux({$ipAddress}, {$type}) Resposne: ".json_encode($response), __LINE__, __FILE__);
	return true;
}

/**
 * deactivates a kernelcare lciense
 * @param $ipAddress ip address to deactivate
 */
function deactivate_kcare($ipAddress)
{
	return deactivate_cloudlinux($ipAddress, 16);
}
