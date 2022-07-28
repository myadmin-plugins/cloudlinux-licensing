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

function cloudlinux_licenses_list()
{
    if ($GLOBALS['tf']->ima == 'admin') {
        require_once __DIR__.'/../../../workerman/statistics/Applications/Statistics/Clients/StatisticClient.php';
        $table = new \TFTable();
        $table->set_title('CloudLinux License List');
        $header = false;
        function_requirements('get_cloudlinux_licenses');
        $licenses = obj2array(get_cloudlinux_licenses());
        $licensesValues = array_values($licenses['data']);
        foreach ($licensesValues as $data) {
            if (!$header) {
                $dataKeys = array_keys($data);
                foreach ($dataKeys as $field) {
                    $table->add_field(ucwords(str_replace('_', ' ', $field)));
                }
                $table->add_row();
                $header = true;
            }
            $dataValues = array_values($data);
            foreach ($dataValues as $field) {
                $table->add_field($field);
            }
            $table->add_row();
        }
        add_output($table->get_table());
    }
}
