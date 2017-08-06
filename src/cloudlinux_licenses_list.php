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

function cloudlinux_licenses_list() {
	if ($GLOBALS['tf']->ima == 'admin') {
		$table = new \TFTable;
		$table->set_title('CloudLinux License List');
		$header = FALSE;
		$licenses = obj2array(get_cloudlinux_licenses());
		$licensesValues = array_values($licenses['data']);
		foreach ($licensesValues as $data) {
			if (!$header) {
				$dataKeys = array_keys($data);
				foreach ($dataKeys as $field)
					$table->add_field(ucwords(str_replace('_', ' ', $field)));
				$table->add_row();
				$header = TRUE;
			}
			$dataValues = array_values($data);
			foreach ($dataValues as $field)
				$table->add_field($field);
			$table->add_row();
		}
		add_output($table->get_table());
	}
}
