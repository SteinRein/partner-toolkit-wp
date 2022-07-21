<?php

namespace SteinRein\Partner\Modules;

use SteinRein\Partner\Settings;

// Make sure this file runs only from within WordPress.
defined( 'ABSPATH' ) or die();

class InquiryForm
{
    public function init() {
        add_shortcode('steinrein_inquiry_form', array($this, 'shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function shortcode() {
        return $this->get_text();
    }

    public function enqueue_scripts() {
        global $post;
        if(!is_archive() && is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'steinrein_inquiry_form')) {
            wp_register_style('steinrein-inquiry-form', false);
            wp_enqueue_style('steinrein-inquiry-form');
            wp_add_inline_style(
                'steinrein-inquiry-form', 
                '.steinrein--layout-alternating-block {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 35px;
                    align-items: center;
                    margin-bottom: 60px;
                }
                .steinrein--layout-alternating-block:nth-child(even) *:nth-child(1) {
                    grid-column: 2;
                }
                .steinrein--layout-alternating-block:nth-child(even) *:nth-child(2) {
                    grid-column: 1;
                    grid-row: 1;
                }
                .steinrein--layout-alternating-column img {
                    width: 100% !important;
                    display: block;
                }
                .steinrein--partner-voucher-code {
                    background-color:#eee;
                    border:1px solid #b4b4b4;
                    border-radius:3px;
                    box-shadow:0 1px 1px rgba(0,0,0,.2),inset 0 2px 0 0 hsla(0,0%,100%,.7);
                    color:#333;
                    display:inline-block;
                    font-size:.85em;
                    font-weight:700;
                    line-height:1;
                    padding:2px 4px;
                    white-space:nowrap;
                }
                
                @media (max-width: 800px) {
                    .steinrein--layout-alternating-block {
                    grid-template-columns: 1fr;
                    justify-items: center;
                    }
                    .steinrein--layout-alternating-block:nth-child(even) *:nth-child(1) {
                    grid-column: unset;
                    }
                    .steinrein--layout-alternating-block:nth-child(even) *:nth-child(2) {
                    grid-row: unset;
                    }
                }'
            );
            
            $options = (new Settings())->get_options();
            if (!$options || !is_array($options)) {
                return;
            }

            $form_id = $options['form_id'] ?? null;
            $form_api_key = $options['form_api_key'] ?? null;
            if (!$form_id || empty($form_id) || !$form_api_key || empty($form_api_key)) {
                return;
            }

            wp_enqueue_script('steinrein-inquiry-form', 'https://partner.steinrein.com/api/form.js?form_id=' . $form_id . '&api_key=' . $form_api_key , [], null, true);
        }        
    }

    public function get_text() {
        $options = (new Settings())->get_options();
        
        $hidden_content_sections = $options['hidden_content_sections'] ?? null;

        $page_content = wp_remote_get('https://partner.steinrein.com/api/form-page.json');

        $text = '';

        if( is_wp_error( $page_content ) ) {
            error_log($page_content->get_error_message());
        } else {
            $page_content = json_decode( wp_remote_retrieve_body( $page_content ) );
            if ($page_content->success && $page_content->data) {

                $text = $page_content->data->intro;
                $text .= '<div id="steinrein-form"></div>';

                $content_sections = $page_content->data->sections;
                if( !empty( $content_sections ) ) {
                    foreach( $content_sections as $content_section ) {
                        if (is_array($hidden_content_sections) && in_array($content_section->title, $hidden_content_sections)) {
                            continue;
                        }

                        ob_start();
                        ?>
                        <div class="steinrein--layout-alternating-block">
                            <div class="steinrein--layout-alternating-column">
                                <a href="<?php echo $content_section->link; ?>" target="_blank">
                                    <img src="<?php echo $content_section->image; ?>" alt="<?php echo $content_section->title; ?>">
                                </a>
                            </div>
                            <div class="steinrein--layout-alternating-column">
                                <h3><a href="<?php echo $content_section->link; ?>" target="_blank"><?php echo $content_section->title; ?></a></h3>
                                <?php echo $content_section->text; ?>
                            </div>
                        </div>
                        <?php
                        $text .= ob_get_clean();
                    }
                }
            }
        }

        return $text;
    }

}