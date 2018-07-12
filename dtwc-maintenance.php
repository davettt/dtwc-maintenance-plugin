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
 * Version:      1.0.2
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

        add_settings_field(
            'dtwc_maintenance_checkbox_field_1',
            __('Mode','dtwc-maintenance'),
            array( $this, 'dtwc_maintenance_checkbox_field_1_callback' ),
            'dtwc-maintenance',
            'dtwc_maintenance_plugin_section',
            array('label_for' =>'dtwc_maintenance_checkbox_field_1')
        );

        add_settings_field(
            'dtwc_maintenance_email',
            __('Email','dtwc-maintenance'),
            array( $this, 'dtwc_maintenance_email_callback' ),
            'dtwc-maintenance',
            'dtwc_maintenance_plugin_section'
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
    Select this to switch to activate
    <?php 
    }

    function dtwc_maintenance_checkbox_field_1_callback() {

        $options = get_option('dtwc_maintenance_settings');

        if( empty( $options ) ) {
            $radio_0 = 1; // set a default
            $radio_1 = 0;
        } else {
            $radio_0 = isset( $options['dtwc_maintenance_checkbox_field_1'] ) && $options['dtwc_maintenance_checkbox_field_1'] === "503"
            ? 1 : 0;
            $radio_1 = isset( $options['dtwc_maintenance_checkbox_field_1'] ) && $options['dtwc_maintenance_checkbox_field_1'] === "200"
            ? 1 : 0;
        }

        ?>
        <input type="radio" id="dtwc_maintenance_checkbox_field_1a" name="dtwc_maintenance_settings[dtwc_maintenance_checkbox_field_1]" <?php checked( $radio_0, 1); ?> value="503">
    Maintenance mode - uses 503<br>
    <input type="radio" id="dtwc_maintenance_checkbox_field_1b" name="dtwc_maintenance_settings[dtwc_maintenance_checkbox_field_1]" <?php checked( $radio_1, 1); ?> value="200">
    Pre-launch mode - uses 200
    <?php 
    }
    
    function dtwc_maintenance_email_callback() {

        $options = get_option('dtwc_maintenance_settings');

        if( !empty( $options ) && isset( $options['dtwc_maintenance_email'] ) ) {
            $email = esc_html( $options['dtwc_maintenance_email'] );
        } else {
            $email = '';
        }

        ?>
        <input type="email" id="dtwc_maintenance_email" name="dtwc_maintenance_settings[dtwc_maintenance_email]" value="<?php echo sanitize_email( $email ); ?>">
    Email contact<br>
    <?php 
    }

}


class DTWC_Maintenance_Switch {

    // define properties
    protected $status;

    public function __construct( $status ) {

        // initialise
        $this->activate_switch = $status['activate_switch'];
        $this->mode = $status['mode'];
        $this->email = $status['email'];
        $this->init_events();
        $this->maintenance_status();

    }

    protected function init_events() {

        // action and filter hooks

    }

    protected function maintenance_status() {

        $switchOn = $this->activate_switch;

        if( $switchOn ) {

            add_action( 'get_header', array( $this, 'maintenance_mode' ) );
            add_filter( 'wp_die_handler', array( $this, 'use_alt_wp_die_handler' ), 10, 3 );

        }

    }

    public function maintenance_mode() {

        $mode = $this->mode;
        $email = $this->email;

        if( ! current_user_can( 'edit_theme_options' ) || ! is_user_logged_in() ) {
            $title= get_bloginfo('title');

            $message = '<h2 style="font-size:18px;color:red">' . $title . ' website is currently offline</h2><p>We are performing scheduled maintenance. We will be back online shortly!</p>';
            $args = 503;

            if( $mode === '200' ) {
                $description = get_bloginfo('description');
                $email_contact = is_email( $email ) ? '<p>Send an email to: <a href="mailto:' . esc_html( antispambot( $email ) ). '">' . esc_html( antispambot( $email ) ) . '</a></p>' : '';

                $message = '<h2 style="font-size:18px;color:orange">' . $title . ' website is in development</h2><p>' . $description . '</p><p>We will be launching soon!' . $email_contact . '</p>';
                $args = 200;
            }
                
            wp_die( $message, $title, $args );
        }

    }

    function use_alt_wp_die_handler() {
        return array( $this, 'alt_wp_die_handler' );
    }

    function alt_wp_die_handler( $message, $title = '', $args = array() ) {
        // a simple copy of the original wp_die_handler function, but with wp_no_robots removed
        $defaults = array( 'response' => 500 );
        $r = wp_parse_args($args, $defaults);
     
        $have_gettext = function_exists('__');
     
        if ( function_exists( 'is_wp_error' ) && is_wp_error( $message ) ) {
            if ( empty( $title ) ) {
                $error_data = $message->get_error_data();
                if ( is_array( $error_data ) && isset( $error_data['title'] ) )
                    $title = $error_data['title'];
            }
            $errors = $message->get_error_messages();
            switch ( count( $errors ) ) {
            case 0 :
                $message = '';
                break;
            case 1 :
                $message = "<p>{$errors[0]}</p>";
                break;
            default :
                $message = "<ul>\n\t\t<li>" . join( "</li>\n\t\t<li>", $errors ) . "</li>\n\t</ul>";
                break;
            }
        } elseif ( is_string( $message ) ) {
            $message = "<p>$message</p>";
        }
     
        if ( isset( $r['back_link'] ) && $r['back_link'] ) {
            $back_text = $have_gettext? __('&laquo; Back') : '&laquo; Back';
            $message .= "\n<p><a href='javascript:history.back()'>$back_text</a></p>";
        }
     
        if ( ! did_action( 'admin_head' ) ) :
            if ( !headers_sent() ) {
                status_header( $r['response'] );
                nocache_headers();
                header( 'Content-Type: text/html; charset=utf-8' );
            }
     
            if ( empty($title) )
                $title = $have_gettext ? __('WordPress &rsaquo; Error') : 'WordPress &rsaquo; Error';
     
            $text_direction = 'ltr';
            if ( isset($r['text_direction']) && 'rtl' == $r['text_direction'] )
                $text_direction = 'rtl';
            elseif ( function_exists( 'is_rtl' ) && is_rtl() )
                $text_direction = 'rtl';
    ?>
    <!DOCTYPE html>
    <html xmlns="http://www.w3.org/1999/xhtml" <?php if ( function_exists( 'language_attributes' ) && function_exists( 'is_rtl' ) ) language_attributes(); else echo "dir='$text_direction'"; ?>>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="viewport" content="width=device-width">
        <title><?php echo $title ?></title>
        <style type="text/css">
            html {
                background: #f1f1f1;
            }
            body {
                background: #fff;
                color: #444;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                margin: 2em auto;
                padding: 1em 2em;
                max-width: 700px;
                -webkit-box-shadow: 0 1px 3px rgba(0,0,0,0.13);
                box-shadow: 0 1px 3px rgba(0,0,0,0.13);
            }
            h1 {
                border-bottom: 1px solid #dadada;
                clear: both;
                color: #666;
                font-size: 24px;
                margin: 30px 0 0 0;
                padding: 0;
                padding-bottom: 7px;
            }
            #error-page {
                margin-top: 50px;
            }
            #error-page p {
                font-size: 14px;
                line-height: 1.5;
                margin: 25px 0 20px;
            }
            #error-page code {
                font-family: Consolas, Monaco, monospace;
            }
            ul li {
                margin-bottom: 10px;
                font-size: 14px ;
            }
            a {
                color: #0073aa;
            }
            a:hover,
            a:active {
                color: #00a0d2;
            }
            a:focus {
                color: #124964;
                -webkit-box-shadow:
                    0 0 0 1px #5b9dd9,
                    0 0 2px 1px rgba(30, 140, 190, .8);
                box-shadow:
                    0 0 0 1px #5b9dd9,
                    0 0 2px 1px rgba(30, 140, 190, .8);
                outline: none;
            }
            .button {
                background: #f7f7f7;
                border: 1px solid #ccc;
                color: #555;
                display: inline-block;
                text-decoration: none;
                font-size: 13px;
                line-height: 26px;
                height: 28px;
                margin: 0;
                padding: 0 10px 1px;
                cursor: pointer;
                -webkit-border-radius: 3px;
                -webkit-appearance: none;
                border-radius: 3px;
                white-space: nowrap;
                -webkit-box-sizing: border-box;
                -moz-box-sizing:    border-box;
                box-sizing:         border-box;
     
                -webkit-box-shadow: 0 1px 0 #ccc;
                box-shadow: 0 1px 0 #ccc;
                vertical-align: top;
            }
     
            .button.button-large {
                height: 30px;
                line-height: 28px;
                padding: 0 12px 2px;
            }
     
            .button:hover,
            .button:focus {
                background: #fafafa;
                border-color: #999;
                color: #23282d;
            }
     
            .button:focus  {
                border-color: #5b9dd9;
                -webkit-box-shadow: 0 0 3px rgba( 0, 115, 170, .8 );
                box-shadow: 0 0 3px rgba( 0, 115, 170, .8 );
                outline: none;
            }
     
            .button:active {
                background: #eee;
                border-color: #999;
                -webkit-box-shadow: inset 0 2px 5px -3px rgba( 0, 0, 0, 0.5 );
                box-shadow: inset 0 2px 5px -3px rgba( 0, 0, 0, 0.5 );
                -webkit-transform: translateY(1px);
                -ms-transform: translateY(1px);
                transform: translateY(1px);
            }
     
            <?php
            if ( 'rtl' == $text_direction ) {
                echo 'body { font-family: Tahoma, Arial; }';
            }
            ?>
        </style>
    </head>
    <body id="error-page">
    <?php endif; // ! did_action( 'admin_head' ) ?>
        <?php echo $message; ?>
    </body>
    </html>
    <?php
        die();
    }

}

function run_dtwc_maintenance_mode() {

    $maintenance_mode_options_checks = get_option('dtwc_maintenance_settings');

    if( !empty( 'maintenance_mode_options_checks' ) && isset( $maintenance_mode_options_checks['dtwc_maintenance_checkbox_field_0'] ) ) {
        $maintenance_switch = $maintenance_mode_options_checks['dtwc_maintenance_checkbox_field_0'];
        $mode = isset( $maintenance_mode_options_checks['dtwc_maintenance_checkbox_field_1'] ) ? $maintenance_mode_options_checks['dtwc_maintenance_checkbox_field_1'] : 0;
        $email = isset( $maintenance_mode_options_checks['dtwc_maintenance_email'] ) ? sanitize_email( $maintenance_mode_options_checks['dtwc_maintenance_email'] ) : 0;
    } else {
        $maintenance_switch = 0;
    }

    if( $maintenance_switch ) {
        $maintenance_switch = array(
            'activate_switch' => $maintenance_switch,
            'mode' => $mode,
            'email' => $email
        );
    }

    $dtwc_maintenance_options = new DTWC_Maintenance_Options();
    $dtwc_maintenance_switch = new DTWC_Maintenance_Switch($maintenance_switch);

}
run_dtwc_maintenance_mode();
