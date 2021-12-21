<?php
/**
 * Plugin Name: Method Gallery
 * Plugin URI: https://github.com/pixelwatt/method-gallery
 * Description: This plugin contains a code library for Method-based themes for creating custom Swiper-based image gallery sliders.
 * Version: 1.0.0
 * Author: Rob Clark
 * Author URI: https://robclark.io
 */

class Method_Gallery {
    protected $items = array();
    protected $opts  = array(
        'css_id' => 'method-gallery-swiper',
        'slide_img_size' => 'large',
    );

    public function __construct(){
        $this->register_dependencies();
        add_action( 'wp_enqueue_scripts', array($this, 'register_dependencies' ));
    }

    // Load dependencies
    public function register_dependencies() {
        wp_enqueue_style( 'swiper', 'https://unpkg.com/swiper@7/swiper-bundle.min.css', '', null );
        wp_enqueue_script( 'swiper', 'https://unpkg.com/swiper@7/swiper-bundle.min.js', array(), null, false );
    }

    public function set_options( $args ) {
        $this->opts = wp_parse_args( $args, $this->opts );
        return;
    }

    public function set_items( $items ) {
        $this->items = $items;
        return;
    }

    public function build_gallery() {
        $output = '';
        if ( $this->items ) {
            if ( is_array( $this->items ) ) {
               // if ( $this->check_key( $this->items[0] ) ) {
                    $output .= '
                        <div id="' . $this->opts['css_id'] . '-wrap">
                            <div id="' . $this->opts['css_id'] . '" class="swiper">
                                <div class="swiper-wrapper">
                    ';
                    foreach ( $this->items as $key => $value ) {
                        $output .= '
                            <div class="swiper-slide">' . wp_get_attachment_image( $key, $this->opts['slide_img_size'], false, array( 'class' => 'img-fluid' ) ) . '</div>
                        ';
                    }
                    $output .= '
                                </div>
                                <div id="' . $this->opts['css_id'] . '-button-prev" class="swiper-button-prev"></div>
                                <div id="' . $this->opts['css_id'] . '-button-next" class="swiper-button-next"></div>
                            </div> <!-- end swiper -->
                        </div> <!-- end outer wrap -->

                        <style>
                            #' . $this->opts['css_id'] . '.swiper {
                                width: 100%;
                                overflow: hidden;
                                position: relative;
                            }
                            #' . $this->opts['css_id'] . '-button-prev,
                            #' . $this->opts['css_id'] . '-button-next {
                                color: #fff;
                                text-shadow: 0 2px 8px rgba(0,0,0,0.25);
                            }
                            #' . $this->opts['css_id'] . '-button-prev {
                                left: 1.5rem;
                            }
                            #' . $this->opts['css_id'] . '-button-next {
                                right: 1.5rem;
                            }
                        </style>

                        <script>
                            const swiper = new Swiper(\'#' . $this->opts['css_id'] . '\', {
                                loop: true,
                                navigation: {
                                    nextEl: \'#' . $this->opts['css_id'] . '-button-next\',
                                    prevEl: \'#' . $this->opts['css_id'] . '-button-prev\',
                                },
                            });
                        </script>
                    ';
                //}
            }
        }
        return $output;
    }

    public function check_key( $key ) {
        $output = false;
        if ( isset( $key ) ) {
            if ( ! empty( $key ) ) {
                $output = true;
            }
        }
        return $output;
    }

}