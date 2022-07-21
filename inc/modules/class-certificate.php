<?php

namespace SteinRein\Partner\Modules;

use SteinRein\Partner\Settings;

// Make sure this file runs only from within WordPress.
defined( 'ABSPATH' ) or die();

class Certificate
{
    public function init() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    function enqueue_scripts() {
        $options = (new Settings())->get_options();
        if (!$options || !is_array($options)) {
            return;
        }

        $partner_id = $options['partner_id'] ?? null;
        $display_certificate = $options['display_certificate'] ?? null;
        if (!$partner_id || empty($partner_id) || !$display_certificate) {
            return;
        }
        
        wp_enqueue_script('steinrein-certificate', 'https://partner.steinrein.com/api/certificate/' . get_option( 'steinrein_toolkit_options' )['partner_id'] . '/main.js' , [], null, true);
    }
}