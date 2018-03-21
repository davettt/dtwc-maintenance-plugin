<?php

/**
 *
 * @link       https://www.davidtiong.com
 * @since      1.0.0
 *
 * @package    DTWC_Maintenance
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$option_name = 'dtwc_maintenance_settings';
 
delete_option($option_name);

