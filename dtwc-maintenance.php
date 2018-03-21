<?php

/**
 * Simple Maintenance Mode plugin
 * 
 * @package DTWC_Maintenance
 * @author  David Tiong
 * 
 * Plugin Name:  DTWC Maintenance
 * Plugin URI:   https://github.com/davettt/dtwc-maintenance-plugin
 * Description:  Simple Maintenance Mode plugin
 * Version:      1.0.0
 * Author:       David Tiong
 * Author URI:   https://www.davidtiong.com
 * License:      GPL3
 * License URI:  https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:  dtwc-maintenance
*/


// No direct access, please
if ( ! defined( 'ABSPATH' ) ) exit;


class DTWC_Maintenance_Options {

    public function __construct() {

        $this->init_events();

    }

    protected function init_events() {
        
        add_action( 'admin_menu', array( $this, 'dtwc_maintenance_options_page' ) );
        add_action( 'admin_init', array( $this, 'dtwc_maintenance_settings_init') );

    }

    function dtwc_maintenance_options_page_html() {

        // check user capabilities
        if( !current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?= esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                // output security fields for the registered setting "dtwc_maintenance_options"
                settings_fields('dtwc_maintenance_options');
                // output setting sections and their fields
                do_settings_sections('dtwc-maintenance');
                // output save settings button
                submit_button('Save Settings');
                ?>
            </form>
        </div>
        <?php
    }

    function dtwc_maintenance_options_page() {

        add_submenu_page(
            'tools.php',
            'DTWC Maintenance Options',
            'DTWC Maintenance Options',
            'manage_options',
            'dtwc-maintenance',
            array( $this, 'dtwc_maintenance_options_page_html' )
        );
    }

    function dtwc_maintenance_settings_init() {

        register_setting( 'dtwc_maintenance_options', 'dtwc_maintenance_settings');

        add_settings_section(
            'dtwc_maintenance_plugin_section',
            __('Plugin Settings Options','dtwc-maintenance'),
            array( $this, 'dtwc_maintenance_settings_section_callback' ),
            'dtwc-maintenance' 
        );
    
        add_settings_field(
            'dtwc_maintenance_checkbox_field_0',
            __('Switch on','dtwc-maintenance'),
            array( $this, 'dtwc_maintenance_checkbox_field_0_callback' ),
            'dtwc-maintenance',
            'dtwc_maintenance_plugin_section',
            array('label_for' =>'dtwc_maintenance_checkbox_field_0')
        );
        
    }

    function dtwc_maintenance_settings_section_callback() {

        echo __('Choose options by checking or unchecking boxes below, then Save Changes.','dtwc-maintenance' );
    
    }

    function dtwc_maintenance_checkbox_field_0_callback() {

        $options = get_option('dtwc_maintenance_settings');

        if( empty( $options ) ) {
            $checkbox_0 = 0;
        } else {
            $checkbox_0 = !isset( $options['dtwc_maintenance_checkbox_field_0'] ) ? 0 : 1;
        }

        ?>
        <input type="checkbox" id="dtwc_maintenance_checkbox_field_0" name="dtwc_maintenance_settings[dtwc_maintenance_checkbox_field_0]" <?php checked( $checkbox_0, 1); ?> value="1">
    Select this to switch on maintenenance mode
    <?php 
    }
    

}


class DTWC_Maintenance_Switch {

    // define properties
    protected $status;

    public function __construct( $status ) {

        // initialise
        $this->maintenance_switch = $status;
        $this->init_events();
        $this->maintenance_status();

    }

    protected function init_events() {

        // action and filter hooks

    }

    protected function maintenance_status() {

        $switchOn = $this->maintenance_switch;

        if( $switchOn ) {

            add_action( 'get_header', array( $this, 'maintenance_mode' ) );

        }

    }

    public function maintenance_mode() {

        if( ! current_user_can( 'edit_theme_options' ) || ! is_user_logged_in() ) {
            $title= get_bloginfo('title');
            $message = '<h2 style="font-size:18px;color:red">' . $title . ' website is currently offline</h2><br />We are performing scheduled maintenance. We will be back online shortly!';
            $args = '503';
            wp_die( $message, $title, $args );
        }

    }

}

function run_dtwc_maintenance_mode() {

    $maintenance_mode_options_checks = get_option('dtwc_maintenance_settings');

    if( !empty( 'maintenance_mode_options_checks' ) && isset( $maintenance_mode_options_checks['dtwc_maintenance_checkbox_field_0'] ) ) {
        $maintenance_switch = $maintenance_mode_options_checks['dtwc_maintenance_checkbox_field_0'];
    } else {
        $maintenance_switch = 0;
    }

    $dtwc_maintenance_options = new DTWC_Maintenance_Options();
    $dtwc_maintenance_switch = new DTWC_Maintenance_Switch($maintenance_switch);

}
run_dtwc_maintenance_mode();
