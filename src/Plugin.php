<?php

namespace Detain\MyAdminCloudlinux;

use Detain\Cloudlinux\Cloudlinux;
use Symfony\Component\EventDispatcher\GenericEvent;
use MyAdmin\Settings;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminCloudlinux
 */
class Plugin {

	public static $name = 'Cloudlinux Licensing';
	public static $description = 'Allows selling of Cloudlinux Server and VPS License Types.  More info at https://www.cloudlinux.com/';
	public static $help = 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a cloudlinux license. Allow 10 minutes for activation.';
	public static $module = 'licenses';
	public static $type = 'service';

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
	}

	/**
	 * @return array
	 */
	public static function getHooks() {
		return [
			'plugin.install' => [__CLASS__, 'getInstall'],
			'plugin.uninstall' => [__CLASS__, 'getUninstall'],
			self::$module.'.settings' => [__CLASS__, 'getSettings'],
			self::$module.'.activate' => [__CLASS__, 'getActivate'],
			self::$module.'.reactivate' => [__CLASS__, 'getActivate'],
			self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
			self::$module.'.deactivate_ip' => [__CLASS__, 'getDeactivateIp'],
			self::$module.'.change_ip' => [__CLASS__, 'getChangeIp'],
			'function.requirements' => [__CLASS__, 'getRequirements'],
			'ui.menu' => [__CLASS__, 'getMenu']
		];
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getInstall(GenericEvent $event) {
		$plugin = $event->getSubject();
		$serviceCategory = $plugin->addServiceCategory(self::$module, 'cloudlinux', 'CloudLinux');
		$plugin->addDefine('SERVICE_TYPES_CLOUDLINUX', $serviceCategory);
		$serviceType = $plugin->addServiceType($serviceCategory, self::$module, 'CloudLinux');
		$plugin->addService($serviceCategory, $serviceType, self::$module, 'CloudLinux License', 10.00, 0, 1, 1, '');
		$plugin->addService($serviceCategory, $serviceType, self::$module, 'CloudLinux Type2 License', 11.95, 0, 1, 2, '');
		$plugin->addService($serviceCategory, $serviceType, self::$module, 'KernelCare License', 2.95, 0, 1, 16, '');
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getUninstall(GenericEvent $event) {
		$plugin = $event->getSubject();
		$plugin->disableServiceCategory(self::$module, 'cloudlinux');
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 * @throws \Detain\Cloudlinux\XmlRpcException
	 */
	public static function getActivate(GenericEvent $event) {
		$serviceClass = $event->getSubject();
		if ($event['category'] == get_service_define('CLOUDLINUX')) {
			myadmin_log(self::$module, 'info', 'Cloudlinux Activation', __LINE__, __FILE__);
			$cl = new Cloudlinux(CLOUDLINUX_LOGIN, CLOUDLINUX_KEY);
			$response = $cl->isLicensed($serviceClass->getIp(), TRUE);
			myadmin_log(self::$module, 'info', 'Response: '.json_encode($response), __LINE__, __FILE__);
			if (!is_array($response) || !in_array($event['field1'], array_values($response))) {
				$response = $cl->license($serviceClass->getIp(), $event['field1']);
				//$serviceExtra = $response['mainKeyNumber'].','.$response['productKey'];
				myadmin_log(self::$module, 'info', 'Response: '.json_encode($response), __LINE__, __FILE__);
			}
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getDeactivate(GenericEvent $event) {
		$serviceClass = $event->getSubject();
		if ($event['category'] == get_service_define('CLOUDLINUX')) {
			myadmin_log(self::$module, 'info', 'Cloudlinux Deactivation', __LINE__, __FILE__);
			function_requirements('deactivate_cloudlinux');
			deactivate_cloudlinux($serviceClass->getIp(), $event['field1']);
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getDeactivateIp(GenericEvent $event) {
		$serviceClass = $event->getSubject();
		if ($event['category'] == get_service_define('CLOUDLINUX')) {
			myadmin_log(self::$module, 'info', 'Cloudlinux Deactivation', __LINE__, __FILE__);
			function_requirements('deactivate_cloudlinux');
			deactivate_cloudlinux($serviceClass->getIp());
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 * @throws \Detain\Cloudlinux\XmlRpcException
	 */
	public static function getChangeIp(GenericEvent $event) {
		if ($event['category'] == get_service_define('CLOUDLINUX')) {
			$serviceClass = $event->getSubject();
			$settings = get_module_settings(self::$module);
			myadmin_log(self::$module, 'info', 'IP Change - (OLD:' .$serviceClass->getIp().") (NEW:{$event['newip']})", __LINE__, __FILE__);
			function_requirements('class.Cloudlinux');
			$cl = new Cloudlinux(CLOUDLINUX_LOGIN, CLOUDLINUX_KEY);
			$response = $cl->removeLicense($serviceClass->getIp(), $event['field1']);
			myadmin_log(self::$module, 'info', 'Response: '.json_encode($response), __LINE__, __FILE__);
			$event['status'] = 'ok';
			$event['status_text'] = 'The IP Address has been changed.';
			if ($response === FALSE) {
				$event['status'] = 'error';
				$event['status_text'] = 'Error removing the old license.';
			} else {
				$response = $cl->isLicensed($event['newip'], TRUE);
				myadmin_log(self::$module, 'info', 'Response: '.json_encode($response), __LINE__, __FILE__);
				if (!is_array($response) || !in_array($event['field1'], array_values($response))) {
					$response = $cl->license($event['newip'], $event['field1']);
					//$serviceExtra = $response['mainKeyNumber'].','.$response['productKey'];
					myadmin_log(self::$module, 'info', 'Response: '.json_encode($response), __LINE__, __FILE__);
					if ($response === FALSE) {
						$event['status'] = 'error';
						$event['status_text'] = 'Error Licensign the new IP.';
					}
				}
			}
			if ($event['status'] == 'ok') {
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $serviceClass->getIp());
				$serviceClass->set_ip($event['newip'])->save();
			}
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getMenu(GenericEvent $event) {
		$menu = $event->getSubject();
		if ($GLOBALS['tf']->ima == 'admin')
			$menu->add_link(self::$module.'api', 'choice=none.cloudlinux_licenses_list', '/images/whm/createacct.gif', 'List all CloudLinux Licenses');
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getRequirements(GenericEvent $event) {
		$loader = $event->getSubject();
		$loader->add_requirement('class.Cloudlinux', '/../vendor/detain/cloudlinux-licensing/src/Cloudlinux.php', '\\Detain\\Cloudlinux\\');
		$loader->add_page_requirement('cloudlinux_licenses_list', '/../vendor/detain/myadmin-cloudlinux-licensing/src/cloudlinux_licenses_list.php');
		$loader->add_requirement('deactivate_kcare', '/../vendor/detain/myadmin-cloudlinux-licensing/src/cloudlinux.inc.php');
		$loader->add_requirement('deactivate_cloudlinux', '/../vendor/detain/myadmin-cloudlinux-licensing/src/cloudlinux.inc.php');
		$loader->add_requirement('get_cloudlinux_licenses', '/../vendor/detain/myadmin-cloudlinux-licensing/src/cloudlinux.inc.php');
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_text_setting(self::$module, 'Cloudlinux', 'cloudlinux_login', 'Cloudlinux Login:', 'Cloudlinux Login', CLOUDLINUX_LOGIN);
		$settings->add_text_setting(self::$module, 'Cloudlinux', 'cloudlinux_key', 'Cloudlinux Key:', 'Cloudlinux Key', CLOUDLINUX_KEY);
		$settings->add_dropdown_setting(self::$module, 'Cloudlinux', 'outofstock_licenses_cloudlinux', 'Out Of Stock CloudLinux Licenses', 'Enable/Disable Sales Of This Type', OUTOFSTOCK_LICENSES_CLOUDLINUX, ['0', '1'], ['No', 'Yes']);
	}

}
