<?php

/**
 * @link              https://www.steinrein.com/
 * @since             1.0.0
 * @package           Steinrein_Partner_Toolkit_WP
 * @author            Bastian Fießinger
 *
 * @wordpress-plugin
 * Plugin Name:       SteinRein Partner Toolkit
 * Plugin URI:        https://www.steinrein.com/
 * Description:       Display various aspects of your SteinRein Partnership in your WordPress site.
 * Version:           1.0.0
 * Author:            SteinRein
 * Author URI:        https://www.steinrein.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       steinrein-toolkit
 * Domain Path:       /languages
 */

namespace SteinRein\Partner;

// Make sure this file runs only from within WordPress.
defined( 'ABSPATH' ) or die();

final class WebsiteToolkit 
{
    function __construct() {
        $this->init();
    }

    function init() {
        $this->define_constants();
        $this->load_files();

        (new Settings())->init();
        (new Modules\Certificate())->init();
        (new Modules\InquiryForm())->init();

        (new Updater())->register();
    }

    function define_constants() {
        if (!defined('STEINREIN_PARTNER_TOOLKIT_PLUGIN_VERSION')) {
            define('STEINREIN_PARTNER_TOOLKIT_PLUGIN_VERSION', '1.0.0');
        }

        if (!defined('STEINREIN_PARTNER_TOOLKIT_PLUGIN_DIR')) {
            define('STEINREIN_PARTNER_TOOLKIT_PLUGIN_DIR', plugin_dir_path(__FILE__));
        }

        if (!defined('STEINREIN_PARTNER_TOOLKIT_PLUGIN_URL')) {
            define('STEINREIN_PARTNER_TOOLKIT_PLUGIN_URL', plugin_dir_url(__FILE__));
        }

        if (!defined('STEINREIN_PARTNER_TOOLKIT_PLUGIN_FILE')) {
            define('STEINREIN_PARTNER_TOOLKIT_PLUGIN_FILE', __FILE__);
        }

        if (!defined('STEINREIN_PARTNER_TOOLKIT_PLUGIN_BASENAME')) {
            define('STEINREIN_PARTNER_TOOLKIT_PLUGIN_BASENAME', plugin_basename(__FILE__));
        }

        if (!defined('STEINREIN_PARTNER_TOOLKIT_PLUGIN_DIR_BASENAME')) {
            define('STEINREIN_PARTNER_TOOLKIT_PLUGIN_DIR_BASENAME', plugin_basename(__DIR__));
        }
    }

    function load_files() {
        require_once plugin_dir_path( __FILE__ ) . 'inc/class-settings.php';
        require_once plugin_dir_path( __FILE__ ) . 'inc/modules/class-certificate.php';
        require_once plugin_dir_path( __FILE__ ) . 'inc/modules/class-inquiry-form.php';
        require_once plugin_dir_path( __FILE__ ) . 'inc/class-updater.php';
    }
}

new WebsiteToolkit();
