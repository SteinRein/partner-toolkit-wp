<?php

namespace SteinRein\Partner;

// Make sure this file runs only from within WordPress.
defined( 'ABSPATH' ) or die();

class Updater
{
    function register() {
        add_filter( 'plugins_api', [$this, 'plugin_info'], 10, 3);
        add_filter( 'site_transient_update_plugins', [$this, 'push_update'] );
        add_filter( 'plugin_row_meta', [$this, 'plugin_row_meta'], 25, 4 );
    }

    /**
     * @param $res
     * @param $action 
     * @param $args
     * @return object
     */
    function plugin_info( $res, $action, $args ){
        // do nothing if this is not about getting plugin information
        if( 'plugin_information' !== $action ) {
            return $res;
        }

        // do nothing if it is not our plugin
        if( STEINREIN_PARTNER_TOOLKIT_PLUGIN_DIR_BASENAME !== $args->slug ) {
            return $res;
        }

        // info.json is the file with the actual plugin information on your server
        $remote = $this->get_plugin_info('{
        	"name" : "SteinRein Partner Toolkit",
            "slug" : "' . STEINREIN_PARTNER_TOOLKIT_PLUGIN_DIR_BASENAME . '",
            "version" : "' . STEINREIN_PARTNER_TOOLKIT_PLUGIN_VERSION . '",
        }');

        // do nothing if we don't get the correct response from the server
        if(!$remote) {
            return $res;	
        }

        $remote = json_decode($remote);

        $plugin_data = get_plugin_data(STEINREIN_PARTNER_TOOLKIT_PLUGIN_FILE);
        
        $res = new \stdClass();
        $res->name = $plugin_data['Name'];
        $res->slug = $remote->slug;
        $res->author = $plugin_data['Author'];
        $res->author_profile = $plugin_data['AuthorURI'];
        $res->version = $plugin_data['Version'];
        $res->requires = $plugin_data['RequiresWP'] ?? null;
        $res->requires_php = $plugin_data['RequiresPHP'] ?? null;
        $res->download_link = $remote->download_url;
        $res->trunk = $remote->download_url;
        $res->last_updated = $remote->last_updated;
        $res->sections = array(
            'description' => $this->plugin_section('Description'),
            'installation' => $this->plugin_section('Installation'),
            'changelog' => $this->plugin_section('Changelog'),
            'faq' => $this->plugin_section('Frequently Asked Questions'),
        );
        // in case you want the screenshots tab, use the following HTML format for its content:
        // <ol><li><a href="IMG_URL" target="_blank"><img src="IMG_URL" alt="CAPTION" /></a><p>CAPTION</p></li></ol>
        if( ! empty( $this->plugin_section('Screenshots') ) ) {
            $res->sections[ 'screenshots' ] = $this->plugin_section('Screenshots');
        }

        $res->banners = array(
            'low' => $this->plugin_banner('banner-772x250.jpg'),
            'high' => $this->plugin_banner('banner-1544x500.jpg'),
        );
        
        return $res;

    }

    function plugin_banner($filename)
    {
        if (file_exists(STEINREIN_PARTNER_TOOLKIT_PLUGIN_DIR . '/' . $filename)) {
            return STEINREIN_PARTNER_TOOLKIT_PLUGIN_URL . '/' . $filename;
        }

        return '';
    }

    function plugin_readme_content()
    {
        $readme = file_get_contents(STEINREIN_PARTNER_TOOLKIT_PLUGIN_DIR . 'readme.txt');
        if(!$readme) {
            return '';
        }

        return $readme;
    }

    /**
     * The Readme file is not hosted on the WordPress repository.
     * That means we need to manually parse the different sections.
     * 
     * All sections start with == {{SECTION_NAME}} == and end with the end of file 
     * or the next section beginning with == {{OTHER_SECTION_NAME}} ==
     * 
     * @return string
     */
    function plugin_section($section_name)
    {
        $readme = $this->plugin_readme_content();
        if(!$readme) {
            return '';
        }

        $section_start = '== ' . $section_name . ' ==';
        $section_end = '== ';

        $section_start_pos = strpos($readme, $section_start);
        if(!$section_start_pos) {
            return '';
        }

        $section_end_pos = strpos($readme, $section_end, $section_start_pos + strlen($section_start));
        if (!$section_end_pos) {
            $section_end_pos = strlen($readme);
        }

        $section_content = substr($readme, $section_start_pos + strlen($section_start), $section_end_pos - $section_start_pos - strlen($section_start));

        if (!class_exists('SteinRein\Partner\MarkdownParser')) {
            require_once STEINREIN_PARTNER_TOOLKIT_PLUGIN_DIR . 'inc/class-markdown-parser.php';
        }

        $rendered = MarkdownParser::render($section_content);

        // remove newlines and line breaks
        $rendered = str_replace(array("\n", "\r"), '', $rendered);

        return $rendered;
    }

    function push_update($transient) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }
    
        $remote = $this->get_plugin_info($res);

        if(!$remote) {
            return $transient;	
        }

        $remote = json_decode($remote);
 
		// your installed plugin version should be on the line below! You can obtain it dynamically of course 
        if(
            $remote
            && version_compare( STEINREIN_PARTNER_TOOLKIT_PLUGIN_VERSION, $remote->version, '<' )
            && version_compare( $remote->requires, get_bloginfo( 'version' ), '<' )
            && version_compare( $remote->requires_php, PHP_VERSION, '<' )
        ) {
            
            $res = new \stdClass();
            $res->slug = $remote->slug;
            $res->plugin = STEINREIN_PARTNER_TOOLKIT_PLUGIN_BASENAME; 
            $res->new_version = $remote->version;
            $res->tested = $remote->tested;
            $res->package = $remote->download_url;
            $transient->response[ $res->plugin ] = $res;
            
            //$transient->checked[$res->plugin] = $remote->version;
        }
    
        return $transient;
    }

    function plugin_row_meta( $links_array, $plugin_file_name, $plugin_data, $status )
    {
        if( $plugin_file_name == STEINREIN_PARTNER_TOOLKIT_PLUGIN_BASENAME ) {

            $links_array[] = sprintf(
                '<a href="%s" class="thickbox open-plugin-details-modal">%s</a>',
                add_query_arg(
                    array(
                        'tab' => 'plugin-information',
                        'plugin' => STEINREIN_PARTNER_TOOLKIT_PLUGIN_DIR_BASENAME,
                        'TB_iframe' => true,
                        'width' => 772,
                        'height' => 852
                    ),
                    admin_url( 'plugin-install.php' )
                ),
                __( 'View details' )
            );
    
        }
    
        return $links_array;
    }

    private function get_plugin_info($res = false) {
        $remote = get_transient('steinrein_partner_toolkit_plugin_info');

        if ( false === $remote ) {
            $response = wp_remote_get( 
                'https://partner.steinrein.com/downloads/wordpress-plugins/steinrein-partner-toolkit/info.json',
                array(
                    'timeout' => 10,
                    'headers' => array(
                        'Accept' => 'application/json'
                    )
                )
            );

            if( 
                is_wp_error( $response )
                || 200 !== wp_remote_retrieve_response_code( $response )
                || empty( wp_remote_retrieve_body( $response ) )
            ) {
                return $res;
            }

            $remote = wp_remote_retrieve_body( $response );
            set_transient( 'steinrein_partner_toolkit_plugin_info', $remote, 5*MINUTE_IN_SECONDS );
        }

        return $remote;
    }

}
