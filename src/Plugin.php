<?php

namespace Detain\MyAdminCloudlinux;

use Detain\Cloudlinux\Cloudlinux;
use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public function __construct() {
	}

	public static function Activate(GenericEvent $event) {
		// will be executed when the licenses.license event is dispatched
		$license = $event->getSubject();
		if ($event['category'] == SERVICE_TYPES_FANTASTICO) {
			myadmin_log('licenses', 'info', 'Cloudlinux Activation', __LINE__, __FILE__);
			function_requirements('activate_cloudlinux');
			activate_cloudlinux($license->get_ip(), $event['field1']);
			$event->stopPropagation();
		}
	}

	public static function ChangeIp(GenericEvent $event) {
		if ($event['category'] == SERVICE_TYPES_FANTASTICO) {
			$license = $event->getSubject();
			$settings = get_module_settings('licenses');
			$cloudlinux = new Cloudlinux(FANTASTICO_USERNAME, FANTASTICO_PASSWORD);
			myadmin_log('licenses', 'info', "IP Change - (OLD:".$license->get_ip().") (NEW:{$event['newip']})", __LINE__, __FILE__);
			$result = $cloudlinux->editIp($license->get_ip(), $event['newip']);
			if (isset($result['faultcode'])) {
				myadmin_log('licenses', 'error', 'Cloudlinux editIp('.$license->get_ip().', '.$event['newip'].') returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__);
				$event['status'] = 'error';
				$event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
			} else {
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $license->get_ip());
				$license->set_ip($event['newip'])->save();
				$event['status'] = 'ok';
				$event['status_text'] = 'The IP Address has been changed.';
			}
			$event->stopPropagation();
		}
	}

	public static function Menu(GenericEvent $event) {
		// will be executed when the licenses.settings event is dispatched
		$menu = $event->getSubject();
		$module = 'licenses';
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link($module, 'choice=none.reusable_cloudlinux', 'icons/database_warning_48.png', 'ReUsable Cloudlinux Licenses');
			$menu->add_link($module, 'choice=none.cloudlinux_list', 'icons/database_warning_48.png', 'Cloudlinux Licenses Breakdown');
			$menu->add_link('licensesapi', 'choice=none.cloudlinux_licenses_list', 'whm/createacct.gif', 'List all Cloudlinux Licenses');
		}
	}

	public static function Requirements(GenericEvent $event) {
		// will be executed when the licenses.loader event is dispatched
		$loader = $event->getSubject();
		$loader->add_requirement('crud_cloudlinux_list', '/../vendor/detain/crud/src/crud/crud_cloudlinux_list.php');
		$loader->add_requirement('crud_reusable_cloudlinux', '/../vendor/detain/crud/src/crud/crud_reusable_cloudlinux.php');
		$loader->add_requirement('get_cloudlinux_licenses', '/licenses/cloudlinux.functions.inc.php');
		$loader->add_requirement('get_cloudlinux_list', '/licenses/cloudlinux.functions.inc.php');
		$loader->add_requirement('cloudlinux_licenses_list', '/licenses/cloudlinux.functions.inc.php');
		$loader->add_requirement('cloudlinux_list', '/licenses/cloudlinux.functions.inc.php');
		$loader->add_requirement('get_available_cloudlinux', '/licenses/cloudlinux.functions.inc.php');
		$loader->add_requirement('activate_cloudlinux', '/licenses/cloudlinux.functions.inc.php');
		$loader->add_requirement('get_reusable_cloudlinux', '/licenses/cloudlinux.functions.inc.php');
		$loader->add_requirement('reusable_cloudlinux', '/licenses/cloudlinux.functions.inc.php');
		$loader->add_requirement('class.cloudlinux', '/../vendor/detain/cloudlinux/class.cloudlinux.inc.php');
		$loader->add_requirement('vps_add_cloudlinux', '/vps/addons/vps_add_cloudlinux.php');
	}

	public static function Settings(GenericEvent $event) {
		// will be executed when the licenses.settings event is dispatched
		$settings = $event->getSubject();
		$settings->add_text_setting('apisettings', 'cloudlinux_username', 'Cloudlinux Username:', 'Cloudlinux Username', $settings->get_setting('FANTASTICO_USERNAME'));
		$settings->add_text_setting('apisettings', 'cloudlinux_password', 'Cloudlinux Password:', 'Cloudlinux Password', $settings->get_setting('FANTASTICO_PASSWORD'));
		$settings->add_dropdown_setting('stock', 'outofstock_licenses_cloudlinux', 'Out Of Stock Cloudlinux Licenses', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_LICENSES_FANTASTICO'), array('0', '1'), array('No', 'Yes', ));
	}

}
