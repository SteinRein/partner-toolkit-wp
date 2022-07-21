<?php

namespace SteinRein\Partner;

// Make sure this file runs only from within WordPress.
defined( 'ABSPATH' ) or die();

class Settings
{
    public $options;

    public function __construct()
    {
        $this->options = get_option('steinrein_toolkit_options');
    }

    public function init() {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    public function add_plugin_page() {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin', 
            'SteinRein Partner Toolkit', 
            'manage_options', 
            'steinrein-toolkit-admin', 
            array( $this, 'create_admin_page' )
        );
    }

    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h2>SteinRein Partner Toolkit</h2>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'steinrein_toolkit_option_group' );
                do_settings_sections( 'steinrein-toolkit-admin' );
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    public function page_init() {
        register_setting(
            'steinrein_toolkit_option_group', // Option group
            'steinrein_toolkit_options', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'steinrein_general_settings', // ID
            'General Settings', // Title
            array( $this, 'print_steinrein_general_settings_info' ), // Callback
            'steinrein-toolkit-admin' // Page
        );  

        add_settings_field(
            'partner_id', // ID
            'Partner ID', // Title 
            array( $this, 'partner_id_callback' ), // Callback
            'steinrein-toolkit-admin', // Page
            'steinrein_general_settings' // Section           
        );

        add_settings_section(
            'steinrein_form_settings', // ID
            'Form settings', // Title
            array( $this, 'print_steinrein_form_settings_info' ), // Callback
            'steinrein-toolkit-admin' // Page
        );  

        add_settings_field(
            'form_id', 
            'Form ID', 
            array( $this, 'form_id_callback' ), 
            'steinrein-toolkit-admin', 
            'steinrein_form_settings'
        );
        
        add_settings_field(
            'form_api_key', 
            'Form API Key', 
            array( $this, 'form_api_key_callback' ), 
            'steinrein-toolkit-admin', 
            'steinrein_form_settings'
        );

        add_settings_field(
            'hidden_content_sections',
            'Hide Content Sections',
            array( $this, 'hidden_content_sections_callback' ),
            'steinrein-toolkit-admin',
            'steinrein_form_settings'
        );

        add_settings_section(
            'steinrein_certificate_settings', // ID
            'Certificate Settings', // Title
            array( $this, 'print_steinrein_certificate_settings_info' ), // Callback
            'steinrein-toolkit-admin' // Page
        );  

        add_settings_field(
            'display_certificate',
            'Display Certificate',
            array( $this, 'display_certificate_callback' ),
            'steinrein-toolkit-admin',
            'steinrein_certificate_settings'
        );
    }

    public function sanitize( $input ) {
        $new_input = array();
        if( isset( $input['partner_id'] ) )
            $new_input['partner_id'] = absint( $input['partner_id'] );

        if( isset( $input['form_id'] ) )
            $new_input['form_id'] = absint( $input['form_id'] );

        if( isset( $input['form_api_key'] ) )
            $new_input['form_api_key'] = sanitize_text_field( $input['form_api_key'] );

        if( isset( $input['hidden_content_sections'] ) )
            $new_input['hidden_content_sections'] = array_map( 'sanitize_text_field', $input['hidden_content_sections'] );

        if( isset( $input['display_certificate'] ) )
            $new_input['display_certificate'] = $input['display_certificate'] ? 1 : 0;

        return $new_input;
    }

    public function print_steinrein_general_settings_info() {
        print 'Enter your general settings below:';
    }
    
    public function print_steinrein_form_settings_info() {
        print 'Enter your form settings below:';
    }

    public function print_steinrein_certificate_settings_info() {
        print 'Enter your certificate settings below:';
    }

    public function partner_id_callback() {
        printf(
            '<input type="number" id="partner_id" name="steinrein_toolkit_options[partner_id]" value="%s" />',
            $this->get_single_option('partner_id') ? esc_attr( $this->get_single_option('partner_id') ) : ''
        );
    }

    public function form_id_callback() {
        printf(
            '<input type="number" id="form_id" name="steinrein_toolkit_options[form_id]" value="%s" />',
            $this->get_single_option('form_id') ? esc_attr( $this->get_single_option('form_id') ) : ''
        );
    }

    public function form_api_key_callback() {
        printf(
            '<input type="text" id="form_api_key" name="steinrein_toolkit_options[form_api_key]" value="%s" />',
            $this->get_single_option('form_api_key') ? esc_attr( $this->get_single_option('form_api_key') ) : ''
        );
    }

    public function display_certificate_callback() {
        printf(
            '<input type="checkbox" id="display_certificate" name="steinrein_toolkit_options[display_certificate]" %s />',
            $this->get_single_option('display_certificate') ? 'checked' : ''
        );
    }

    public function hidden_content_sections_callback() {
        $page_content = wp_remote_get('http://partner-admin.test/api/form-page.json');

        if( is_wp_error( $page_content ) ) {
            echo '<p>' . $page_content->get_error_message() . '</p>';
        } else {
            $page_content = json_decode( wp_remote_retrieve_body( $page_content ) );
            if ($page_content->success && $page_content->data) {
                $content_sections = $page_content->data->sections;
                if( !empty( $content_sections ) ) {
                    foreach( $content_sections as $content_section ) {
                        echo '<label>';
                        echo '<input type="checkbox" name="steinrein_toolkit_options[hidden_content_sections][]" value="' . $content_section->title . '" ' . ( is_array($this->get_single_option('hidden_content_sections')) && in_array( $content_section->title, $this->get_single_option('hidden_content_sections') ) ? 'checked' : '' ) . '>' . $content_section->title . '<br>';
                        echo '</label>';
                    }
                }
            }
        }
    }

    public function get_options() {
        return $this->options;
    }

    public function get_single_option( $option ) {
        if (isset($this->get_options()[$option])) {
            return $this->get_options()[$option];
        }

        return null;
    }
}