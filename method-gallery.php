<?php
/**
 * Plugin Name: Method Gallery
 * Plugin URI: https://github.com/pixelwatt/method-gallery
 * Description: This plugin adds filterable galleries to WordPress, called via shortcode. CMB2 and a Bootstrap 5 theme are required.
 * Version: 2.0.0-beta2
 * Author: Rob Clark
 * Author URI: https://robclark.io
 */

function method_gallery_enqueue_dependencies() {
    wp_enqueue_style( 'swiper', plugin_dir_url( __FILE__ ) . 'inc/swiper/swiper-bundle.min.css', '', '9.0.5' );
    wp_enqueue_script( 'swiper', plugin_dir_url( __FILE__ ) . 'inc/swiper/swiper-bundle.min.js', array(), '9.0.5', false );

    wp_enqueue_style( 'glightbox', plugin_dir_url( __FILE__ ) . 'inc/glightbox/glightbox.min.css', '', '3.2.0' );
    wp_enqueue_script( 'glightbox', plugin_dir_url( __FILE__ ) . 'inc/glightbox/glightbox.min.js', array( 'jquery' ), '3.2.0', false );

    wp_enqueue_style( 'method-gallery', plugin_dir_url( __FILE__ ) . 'assets/css/method-gallery.css', '', '2.0.0-beta2' );
}

add_action( 'wp_enqueue_scripts', 'method_gallery_enqueue_dependencies' );

function method_gallery_get_term_array( $tax, $none = '', $hide_empty = true ) {
    //lets create an array of boroughs to loop through
    if ( ! empty( $none ) ) {
        $output[0] = $none;
    } else {
        $output = array();
    }

    //The Query
    if ( ! $hide_empty ) {
        $items = get_terms( $tax, array(
            'hide_empty' => false,
        ) );
    } else {
        $items = get_terms( $tax );
    }

    if ( $items ) {
        foreach ( $items as $term ) :
            $output[ "{$term->term_id}" ] = $term->name;
        endforeach;
    }

    return $output;
}

function method_gallery_get_image_size_array( $default = '' ) {
    $sizes = get_intermediate_image_sizes();
    $options = array();
    if ( ! empty( $default ) ) {
        $options[""] = $default;
    }
    if ( is_array( $sizes ) ) {
        if ( 1 <= count( $sizes ) ) {
            foreach ( $sizes as $size ) {
                $options["{$size}"] = $size;
            }
        }
    }
    if ( ! array_key_exists( 'full', $options ) ) {
        $options["full"] = "full";
    }
    return $options;
}

//-----------------------------------------------------
// Register a post type for the galleries
//-----------------------------------------------------

add_action( 'init', 'method_gallery_init' );

function method_gallery_init() {
    $labels = array(
        'name'               => _x( 'Galleries', 'post type general name', 'method-alerts' ),
        'singular_name'      => _x( 'Gallery', 'post type singular name', 'method-alerts' ),
        'menu_name'          => _x( 'Galleries', 'admin menu', 'method-alerts' ),
        'name_admin_bar'     => _x( 'Gallery', 'add new on admin bar', 'method-alerts' ),
        'add_new'            => _x( 'Add Gallery', 'job', 'method-alerts' ),
        'add_new_item'       => __( 'Add New Gallery', 'method-alerts' ),
        'new_item'           => __( 'New Gallery', 'method-alerts' ),
        'edit_item'          => __( 'Edit Gallery', 'method-alerts' ),
        'view_item'          => __( 'View Gallery', 'method-alerts' ),
        'all_items'          => __( 'Galleries', 'method-alerts' ),
        'search_items'       => __( 'Search Galleries', 'method-alerts' ),
        'parent_item_colon'  => __( 'Parent Gallery:', 'method-alerts' ),
        'not_found'          => __( 'No galleries found.', 'method-alerts' ),
        'not_found_in_trash' => __( 'No galleries found in Trash.', 'method-alerts' ),
    );

    $args = array(
        'labels'             => $labels,
        'description'        => __( 'Image galleries.', 'method-alerts' ),
        'public'             => false,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'query_var'          => true,
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => 11,
        'menu_icon'          => 'dashicons-format-gallery',
        'supports'           => array( 'title' ),
    );

    register_post_type( 'method_gallery', $args );
}

//-----------------------------------------------------
// Add a taxonomy for gallery filters
//-----------------------------------------------------

add_action( 'init', 'method_register_gallery_filters', 0 );

function method_register_gallery_filters() {
    // Add new taxonomy, make it hierarchical (like categories)
    $labels = array(
        'name' => _x( 'Gallery Filters', 'taxonomy general name' ),
        'singular_name' => _x( 'Gallery Filter', 'taxonomy singular name' ),
        'search_items' =>  __( 'Search Gallery Filters' ),
        'all_items' => __( 'All Gallery Filters' ),
        'parent_item' => __( 'Gallery Filter' ),
        'parent_item_colon' => __( 'Gallery Filter:' ),
        'edit_item' => __( 'Edit Gallery Filter' ),
        'update_item' => __( 'Update Gallery Filter' ),
        'add_new_item' => __( 'Add New Gallery Filter' ),
        'new_item_name' => __( 'New Gallery Filter Name' ),
        'menu_name' => __( 'Gallery Filters' ),
    );

    register_taxonomy('method_gallery_filters',array('attachment'), array(
        'hierarchical' => true,
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'query_var' => false,
        'archive' => false,
        'show_admin_column' => true
    ));
}


function method_gallery_add_media_custom_field( $form_fields, $post ) { 
    $show_method_filters = true;
    $screen = get_current_screen();
    if ( is_object( $screen ) ) {
        if ( ( property_exists( $screen, 'base' ) ) && ( property_exists( $screen, 'id' ) ) ) {
            if ( ( $screen->base == 'post' ) && ( $screen->id == 'attachment' ) ) {
                $show_method_filters = false;
            } 
        }
    }
    if ( $show_method_filters ) {
        // Getting all the users
        $props = method_gallery_get_term_array( 'method_gallery_filters', '', false );
        $tag_html = '';
        foreach ( $props as $key => $value ) {
            // Adding the checkbox HTML with the 'checked="checked"' attribute if the user ID is a tag key
            $tag_html .= '<input type="checkbox" name="attachments[' . $post->ID . '][method_filters][' . $key . ']"' . ( has_term( (int) $key, 'method_gallery_filters', $post->ID ) ? ' checked="checked"' : '') . '> ' . $value . '<br/>';
        }
        
        // Adding the tag field
        $form_fields['method_filters'] = array( 
            'label' => __( 'Filters:' ),
            'input'  => 'html',
            'html' => $tag_html
        );
    }

    
    $method_gallery_show_title = (bool) get_post_meta($post->ID, 'method_gallery_show_title', true);
    $form_fields['method_gallery_show_title'] = array(
        'label' => 'Title:',
        'input' => 'html',
        'html' => '<input type="checkbox" id="attachments-'.$post->ID.'-method_gallery_show_title" name="attachments['.$post->ID.'][method_gallery_show_title]" value="1"'.($method_gallery_show_title ? ' checked="checked"' : '').' /> Include In Gallery View',
        'value' => $method_gallery_show_title,
        'helps' => ''
    );

    $method_gallery_show_desc = (bool) get_post_meta($post->ID, 'method_gallery_show_desc', true);
    $form_fields['method_gallery_show_desc'] = array(
        'label' => 'Description:',
        'input' => 'html',
        'html' => '<input type="checkbox" id="attachments-'.$post->ID.'-method_gallery_show_desc" name="attachments['.$post->ID.'][method_gallery_show_desc]" value="1"'.($method_gallery_show_desc ? ' checked="checked"' : '').' /> Include In Gallery View',
        'value' => $method_gallery_show_desc,
        'helps' => ''
    );
    
  
    return $form_fields;
}
add_filter( 'attachment_fields_to_edit', 'method_gallery_add_media_custom_field', 1, 2 );


function method_gallery_save_custom_checkbox_attachment_field( $post, $attachment ) {
    if ( is_array( $attachment ) ) {
        // Filters save
        if ( array_key_exists( 'method_filters', $attachment ) ) {
            if ( is_array( $attachment['method_filters'] ) ) {
                $tags = $attachment['method_filters'];
                $newterms = array();
                foreach( $tags as $key => $value ) {
                    if ( $key !== null ) {
                        $newterms[] = $key;
                    }
                }
                if ( array_key_exists( 0, $newterms ) ) {
                    wp_set_object_terms( $post['ID'], $newterms, 'method_gallery_filters', false );
                } else {
                    wp_set_object_terms( $post['ID'], null, 'method_gallery_filters', false );
                }
            } else {
                wp_set_object_terms( $post['ID'], null, 'method_gallery_filters', false );
            }
        } else {
            wp_set_object_terms( $post['ID'], null, 'method_gallery_filters', false );
        }
        // Title visibility save
        if ( array_key_exists( 'method_gallery_show_title', $attachment ) ) {
            update_post_meta($post['ID'], 'method_gallery_show_title', sanitize_text_field( $attachment['method_gallery_show_title'] ) );  
        } else {
            delete_post_meta($post['ID'], 'method_gallery_show_title' );
        }
        // Description visibility save
        if ( array_key_exists( 'method_gallery_show_desc', $attachment ) ) {
            update_post_meta($post['ID'], 'method_gallery_show_desc', sanitize_text_field( $attachment['method_gallery_show_desc'] ) );  
        } else {
            delete_post_meta($post['ID'], 'method_gallery_show_desc' );
        }
    }
    return $post;
}

add_filter('attachment_fields_to_save', 'method_gallery_save_custom_checkbox_attachment_field', 1, 2);


// Add gallery metabox

add_action( 'cmb2_admin_init', 'method_gallery_gallery_metabox' );
/**
 * Define the metabox and field configurations.
 */
function method_gallery_gallery_metabox() {

    /**
     * Initiate the metabox
     */
    $cmb = new_cmb2_box( array(
        'id'            => 'method_gallery_gallery_metabox',
        'title'         => __( 'Gallery Configuration', 'cmb2' ),
        'object_types'  => array( 'method_gallery', ), // Post type
        'context'       => 'normal',
        'priority'      => 'high',
        'show_names'    => true, // Show field names on the left
        // 'cmb_styles' => false, // false to disable the CMB stylesheet
        // 'closed'     => true, // Keep the metabox closed by default
    ) );

    $cmb->add_field( array(
        'name' => 'Images',
        'desc' => 'Add and configure images to display in this gallery.',
        'type' => 'file_list',
        'id'   => '_method_gallery_items',
        'preview_size' => ( wp_is_mobile() ? array( 50, 50 ) : array( 150, 150 )  ),
    ) );

    $cmb->add_field( array(
        'name' => 'Display Options',
        'desc' => 'Configure how this gallery is displayed.',
        'type' => 'title',
        'id'   => '_method_gallery_display_options'
    ) );

    $cmb->add_field( array(
        'name'    => 'Display Format',
        'id'      => '_method_gallery_format',
        'type'    => 'radio_inline',
        'options' => array(
            'swiper' => __( 'Swiper Slides', 'cmb2' ),
            'grid'   => __( 'Image Grid', 'cmb2' ),
        ),
        'default' => 'swiper',
        'desc' => 'Choose how to display this gallery, and configure format-specific options below. To configure default sizes and rations, visit the plugin\'s <a href="/wp-admin/options-general.php?page=method_gallery_options">settings page</a>.',
        'classes' => 'cmb-row-flush-bottom',
    ) );

    // -----------------------------------------
    // START SWIPER OPTIONS
    // -----------------------------------------

    $cmb->add_field( array(
        'name' => 'Slide Image Size',
        'type'    => 'select',
        'options' => method_gallery_get_image_size_array( 'Default' ),
        'default' => '',
        'id'   => '_method_gallery_display_swiper_size',
        'before_row' => '<div id="method-gallery-swiper-options-wrap" class="method-gallery-display-options-wrap">',
    ) );

    $cmb->add_field( array(
        'name'    => 'Slide Aspect Ratio',
        'id'      => '_method_gallery_display_swiper_aspect',
        'type'    => 'radio_inline',
        'options' => array(
            ''    => __( 'Default', 'cmb2' ),
            '1:1' => __( '1:1', 'cmb2' ),
            '4:3' => __( '4:3', 'cmb2' ),
            '3:2' => __( '3:2', 'cmb2' ),
            '8:5' => __( '8:5', 'cmb2' ),
            '16:9' => __( '16:9', 'cmb2' ),
        ),
        'default' => '',
    ) );

    $cmb->add_field( array(
        'name'    => 'Display Extra Content?',
        'id'      => '_method_gallery_extra',
        'desc'    => 'When only showing one slide at a time (<em>Images Per Row</em> is set to <em>One</em>), check here to include any image titles or descriptions configured to be visible as part of each image\'s swiper slide.',
        'type'    => 'checkbox',
        'after_row' => '</div>',
    ) );

    // -----------------------------------------
    // END SWIPER OPTIONS
    // -----------------------------------------

    // -----------------------------------------
    // START GRID OPTIONS
    // -----------------------------------------

    $cmb->add_field( array(
        'name' => 'Grid Image Size',
        'type'    => 'select',
        'options' => method_gallery_get_image_size_array( 'Default' ),
        'default' => '',
        'id'   => '_method_gallery_display_grid_size',
        'before_row' => '<div id="method-gallery-grid-options-wrap" class="method-gallery-display-options-wrap">',
    ) );

    $cmb->add_field( array(
        'name'    => 'Grid Image Aspect Ratio',
        'id'      => '_method_gallery_display_grid_aspect',
        'type'    => 'radio_inline',
        'options' => array(
            ''    => __( 'Default', 'cmb2' ),
            '1:1' => __( '1:1', 'cmb2' ),
            '4:3' => __( '4:3', 'cmb2' ),
            '3:2' => __( '3:2', 'cmb2' ),
            '8:5' => __( '8:5', 'cmb2' ),
            '16:9' => __( '16:9', 'cmb2' ),
        ),
        'default' => '',
        'after_row' => '</div>',
    ) );

    // -----------------------------------------
    // END GRID OPTIONS
    // -----------------------------------------

    $cmb->add_field( array(
        'name'    => 'Images Per Row',
        'id'      => '_method_gallery_imgs_per_row',
        'type'    => 'radio_inline',
        'options' => array(
            1 => __( 'One', 'cmb2' ),
            2 => __( 'Two', 'cmb2' ),
            3 => __( 'Three', 'cmb2' ),
            4 => __( 'Four', 'cmb2' ),
            6 => __( 'Six', 'cmb2' ),
        ),
        'default' => 1,
    ) );

    $cmb->add_field( array(
        'name'    => 'Use Lightboxes?',
        'id'      => '_method_gallery_lightbox',
        'desc'    => 'Check here to enable lightboxes for this gallery. If enabled, clicking an image will open a larger view in a full-screen lightbox. For galleries displayed as image grids, keyboard navigation will be available.',
        'type'    => 'checkbox',
    ) );

    

    // -----------------------------------------
    // FILTER OPTIONS
    // -----------------------------------------

    $cmb->add_field( array(
        'name' => 'Filter Options',
        'desc' => 'Configure AJAX filtering for this gallery.',
        'type' => 'title',
        'id'   => '_method_gallery_filter_options'
    ) );

    $cmb->add_field( array(
        'name'    => 'Enable Filtering?',
        'id'      => '_method_gallery_filter_enable',
        'desc'    => 'Check here to enable filtering for this gallery.',
        'type'    => 'checkbox',
    ) );

    $cmb->add_field( array(
        'name'    => 'Filter Format',
        'id'      => '_method_gallery_filter_format',
        'type'    => 'radio_inline',
        'options' => array(
            'btns' => __( 'Buttons', 'cmb2' ),
            'select'   => __( 'Select Box', 'cmb2' ),
        ),
        'default' => 'btns',
    ) );

    $cmb->add_field( array(
        'name'    => 'Included Filters',
        'id'      => '_method_gallery_filter_items',
        'type'    => 'select',
        'options' => method_gallery_get_term_array( 'method_gallery_filters', '', false ),
        'show_option_none' => true,
        'default' => '',
        'repeatable' => true,
        'text' => array(
            'add_row_text' => '+ Add Another Filter',
        ),
    ) );

}


add_action( 'cmb2_admin_init', 'method_gallery_shortcode_metabox' );
/**
 * Define the metabox and field configurations.
 */
function method_gallery_shortcode_metabox() {

    // Get the post ID
    $post_id = null;
    if ( isset( $_GET['post'] ) ) {
        $post_id = $_GET['post'];
    } elseif ( isset( $_POST['post_ID'] ) ) {
        $post_id = $_POST['post_ID'];
    }

    /**
     * Metabox to add fields to categories and tags
     */
    $cmb_options = new_cmb2_box(
        array(
            'id'           => 'method_gallery_shortcode_metabox',
            'title'        => __( 'Shortcode', 'grace' ), // Doesn't output for term boxes
            'object_types' => 'method_gallery',
            'context'      => 'side',
            'priority'     => 'low',
        )
    );

    $cmb_options->add_field(
        array(
            'desc' => __( ( ! empty( $post_id ) ? '<input type="text" onfocus="this.select();" readonly="readonly" value="[method_gallery id=\'' . $post_id . '\']" class="shortcode-in-list-table wp-ui-text-highlight code"><span style="display: block; margin-top: 16px;">Use the shortcode above to display this gallery.</span>' : 'Save this gallery to view it\'s shortcode.' ), 'cmb2' ),
            'id'   => '_method_gallery_shortcode_info',
            'type' => 'title',
        )
    );

}


//======================================================================
// PLUGIN OPTIONS
//======================================================================

add_action( 'cmb2_admin_init', 'method_gallery_plugin_options_metabox' );

function method_gallery_plugin_options_metabox() {
    $cmb_options = new_cmb2_box(
        array(
            'id'           => 'method_gallery_plugin_options_metabox',
            'title'        => esc_html__( 'Method Gallery Settings', 'whitespring' ),
            'object_types' => array( 'options-page' ),

            /*
             * The following parameters are specific to the options-page box
             * Several of these parameters are passed along to add_menu_page()/add_submenu_page().
             */

            'option_key'      => 'method_gallery_options', // The option key and admin menu page slug.
            // 'icon_url'        => 'dashicons-palmtree', // Menu icon. Only applicable if 'parent_slug' is left empty.
            'menu_title'      => esc_html__( 'Method Gallery', 'whitespring' ), // Falls back to 'title' (above).
            'parent_slug'     => 'options-general.php', // Make options page a submenu item of the themes menu.
            // 'capability'      => 'manage_options', // Cap required to view options-page.
            // 'position'        => 1, // Menu position. Only applicable if 'parent_slug' is left empty.
            // 'admin_menu_hook' => 'network_admin_menu', // 'network_admin_menu' to add network-level options page.
            // 'display_cb'      => false, // Override the options-page form output (CMB2_Hookup::options_page_output()).
            // 'save_button'     => esc_html__( 'Save Theme Options', 'myprefix' ), // The text for the options-page save button. Defaults to 'Save'.
        )
    );

    $cmb_options->add_field( array(
        'name' => 'Theme Configuration',
        //'desc' => 'This is a title description',
        'type' => 'title',
        'id'   => 'theme_config_info'
    ) );

    $cmb_options->add_field( array(
        'name'    => 'Bootstrap Grid Configuration',
        'id'      => 'grid_cols',
        'type'    => 'radio_inline',
        'options' => array(
            12 => __( '12 Columns', 'cmb2' ),
            24 => __( '24 Columns', 'cmb2' ),
        ),
        'default' => 12,
    ) );

    $cmb_options->add_field( array(
        'name' => 'Swiper Options',
        //'desc' => 'This is a title description',
        'type' => 'title',
        'id'   => 'swiper_config_info'
    ) );

    $cmb_options->add_field( array(
        'name'    => 'Swiper Slide Aspect Ratio',
        'id'      => 'swiper_aspect',
        'type'    => 'radio_inline',
        'options' => array(
            '1:1' => __( '1:1', 'cmb2' ),
            '4:3' => __( '4:3', 'cmb2' ),
            '3:2' => __( '3:2', 'cmb2' ),
            '8:5' => __( '8:5', 'cmb2' ),
            '16:9' => __( '16:9', 'cmb2' ),
        ),
        'default' => '16:9',
    ) );

    $cmb_options->add_field( array(
        'name'    => 'Swiper Slide Image Size',
        'id'      => 'swiper_size',
        'type'    => 'select',
        'options' => method_gallery_get_image_size_array(),
        'default' => 'large',
    ) );

    $cmb_options->add_field( array(
        'name' => 'Image Grid Options',
        //'desc' => 'This is a title description',
        'type' => 'title',
        'id'   => 'grid_config_info'
    ) );

    $cmb_options->add_field( array(
        'name'    => 'Grid Image Aspect Ratio',
        'id'      => 'grid_aspect',
        'type'    => 'radio_inline',
        'options' => array(
            '1:1' => __( '1:1', 'cmb2' ),
            '4:3' => __( '4:3', 'cmb2' ),
            '3:2' => __( '3:2', 'cmb2' ),
            '8:5' => __( '8:5', 'cmb2' ),
            '16:9' => __( '16:9', 'cmb2' ),
        ),
        'default' => '1:1',
    ) );

    $cmb_options->add_field( array(
        'name'    => 'Grid Image Size',
        'id'      => 'grid_size',
        'type'    => 'select',
        'options' => method_gallery_get_image_size_array(),
        'default' => 'large',
    ) );

    $cmb_options->add_field( array(
        'name' => 'Lightbox Options',
        //'desc' => 'This is a title description',
        'type' => 'title',
        'id'   => 'lightbox_config_info'
    ) );

    $cmb_options->add_field( array(
        'name'    => 'Lightbox Image Size',
        'id'      => 'lightbox_size',
        'type'    => 'select',
        'options' => method_gallery_get_image_size_array(),
        'default' => 'full',
    ) );

    // method_gallery_get_image_size_array


}

// Register the shortcode

function method_gallery_shortcode_func( $atts ) {
    $a = shortcode_atts( array(
        'id' => '',
    ), $atts );
    if ( ! empty( $a['id'] ) ) {
        $gallery = new Method_Gallery_v2;
        return $gallery->build_gallery( $a['id'] );
    } else {
        return;
    }
}

add_shortcode( 'method_gallery', 'method_gallery_shortcode_func' );


// Class to build the gallery

class Method_Gallery_v2 {
    protected $meta    = array();
    protected $opts    = array();
    protected $id;
    protected $html;
    protected $scripts;
    protected $filters = array();
    protected $sizes   = array();
    protected $rsizes  = array();
    protected $active_filter;

    public function build_gallery( $gid ) {
        $this->id   = $gid;
        $this->meta = get_post_meta( $this->id );
        $this->opts = get_option( 'method_gallery_options' );
        $this->check_filters();
        $this->check_sizes();
        $this->build_gallery_markup();
        return $this->html . '<script>jQuery(function() {' . $this->scripts . '});</script>';
    }

    public function rebuild_gallery( $gid, $active ) {
        $this->id   = $gid;
        $this->meta = get_post_meta( $this->id );
        $this->opts = get_option( 'method_gallery_options' );
        if ( ! empty( $active ) ) {
            $this->active_filter = $active;
        }
        $this->build_body();
        return $this->html;
    }

    private function build_gallery_markup() {
        $this->html .= '<div id="method-gallery-' . $this->id . '" class="method-gallery">';
        $this->build_filters();
        $this->html .= '<div class="method-gallery-body method-gallery-body-' . $this->get_meta( '_method_gallery_format', 'swiper' ) . '-format">';
        $this->build_body();
        $this->html .= '</div> <!-- end method-gallery-body -->';
        $this->html .= '</div> <!-- end #method-gallery-' . $this->id . ' -->';
    }

    private function build_filters() {
        if ( 'on' == $this->get_meta( '_method_gallery_filter_enable' ) ) {
            $this->html .= '<div class="method-gallery-filters method-gallery-filters-' . $this->get_meta( '_method_gallery_filter_format', 'btns' ) . '-format"><strong>Filter Images: </strong>';
            if ( 'select' == $this->get_meta( '_method_gallery_filter_format' ) ) {
                $this->html .= '<div class="method-gallery-filter-select-wrap"><select name="method-gallery-' . $this->id . '-filter-select" id="method-gallery-' . $this->id . '-filter-select"><option value=""' . ( empty( $this->active_filter ) ? ' selected' : '' ) . '>Show All</option>';
                if ( $this->check_array_key( $this->filters, 0 ) ) {
                    foreach ( $this->filters as $filter ) {
                        $term = get_term( $filter );
                        $this->html .= '<option value="' . $filter . '"' . ( $this->active_filter == $filter ? ' selected' : '' ) . '>' . $term->name . '</option>';
                    }
                }
                $this->html .= '</select><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-chevron-down" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"></path></svg></div>';
                $this->scripts .= '
                    jQuery(\'select#method-gallery-' . $this->id . '-filter-select\').on(\'change\', function() {
                        selected_value = this.value;
                        jQuery(\'#method-gallery-' . $this->id . '-build #active\').val(selected_value);
                        jQuery( "#method-gallery-' . $this->id . '-build" ).submit();
                    });
                ';
            } else {
                $this->html .= '<label for="method-gallery-' . $this->id . '-filter-btns-1"><input type="radio" id="method-gallery-' . $this->id . '-filter-btns-1" name="method-gallery-' . $this->id . '-filter-btns" value=""' . ( empty( $this->active_filter ) ? ' checked' : '' ) . '><span>Show All</span></label>';
                if ( $this->check_array_key( $this->filters, 0 ) ) {
                    $i = 2;
                    foreach ( $this->filters as $filter ) {
                        $term = get_term( $filter );
                        $this->html .= '<label for="method-gallery-' . $this->id . '-filter-btns-' . $i . '"><input type="radio" id="method-gallery-' . $this->id . '-filter-btns-' . $i . '" name="method-gallery-' . $this->id . '-filter-btns" value="' . $filter . '"' . ( $this->active_filter == $filter ? ' checked' : '' ) . '><span>' . $term->name . '</span></label>';
                        $i++;
                    }
                }
                $this->scripts .= '
                    jQuery("input[name=\'method-gallery-' . $this->id . '-filter-btns\']").change(function(){
                        selected_value = jQuery("input[name=\'method-gallery-' . $this->id . '-filter-btns\']:checked").val();
                        jQuery(\'#method-gallery-' . $this->id . '-build #active\').val(selected_value);
                        jQuery( "#method-gallery-' . $this->id . '-build" ).submit();
                    });
                ';
            }
            $this->html .= '</div> <!-- end method-gallery-filters -->';
            $this->scripts .= '
                jQuery(\'#method-gallery-' . $this->id . '-build\').submit(function(){
                    var filter = jQuery(\'#method-gallery-' . $this->id . '-build\');
                    jQuery.ajax({
                        url:filter.attr(\'action\'),
                        data:filter.serialize(), // form data
                        type:filter.attr(\'method\'), // POST
                        beforeSend:function(xhr){
                            // transition to go here
                        },
                        success:function(data){
                            jQuery(\'#method-gallery-' . $this->id . ' .method-gallery-body\').html(data);
                            ' . ( 'on' == $this->get_meta( '_method_gallery_lightbox' ) ? 'lightbox' . $this->id . '.reload();' : '' ) . '
                            ' . ( 'grid' == $this->get_meta( '_method_gallery_format' ) ? '' : 'add_swiper' . $this->id . '();' ) . '
                        }
                    });
                    
                    return false;
                });

            ';
            $this->html .= '
                <form action="' . site_url() . '/wp-admin/admin-ajax.php" method="POST" id="method-gallery-' . $this->id . '-build" style="display: none; visibility: hidden;">
                    <input type="hidden" name="active" id="active" value="' . $this->active_filter . '">
                    <input type="hidden" name="gid" id="gid" value="' . $this->id . '">
                    <input type="hidden" name="action" id="action" value="method_gallery_rebuild_body">
                </form>
            ';
        }
    }

    private function build_body() {
        $images = $this->get_serialized_meta( '_method_gallery_items' );
        if ( is_array( $images ) ) {
            if ( 1 <= count( $images ) ) {
                $per_row = $this->get_meta( '_method_gallery_imgs_per_row', 1 );
                $grid_cols = $this->get_option( 'grid_cols', 12 );
                if ( 'on' == $this->get_meta( '_method_gallery_lightbox' ) ) {
                    $this->scripts = '
                        var lightbox' . $this->id . ' = GLightbox({
                            touchNavigation: ' . ( 'grid' == $this->get_meta( '_method_gallery_format' ) ? 'true' : 'false' ) . ',
                            keyboardNavigation: ' . ( 'grid' == $this->get_meta( '_method_gallery_format' ) ? 'true' : 'false' ) . ',
                            draggable: ' . ( 'grid' == $this->get_meta( '_method_gallery_format' ) ? 'true' : 'false' ) . ',
                            loop: true,
                            selector: \'.glightbox' . $this->id . '\',
                        });
                    ' . $this->scripts;
                }
                if ( 'grid' == $this->get_meta( '_method_gallery_format' ) ) {
                    switch ( $per_row ) {
                        case 6:
                        case 4:
                        case 3:
                            $col_array = array(
                                'col-' . $grid_cols,
                                'col-sm-' . ( $grid_cols / 2 ),
                                'col-md-' . ( $grid_cols / 3 ),
                                'col-xl-' . ( $grid_cols / $per_row ),
                            );
                            break;
                        case 2:
                            $col_array = array(
                                'col-' . $grid_cols,
                                'col-md-' . ( $grid_cols / 2 ),
                            );
                            break;
                        default:
                            $col_array = array(
                                'col-' . $grid_cols,
                            );
                            break;
                    }
                    $this->html .= '<div class="row">';
                    foreach ( $images as $key => $value ) {
                        if ( $this->check_item_visibility( $key ) ) {
                            $this->html .= '
                                <div class="' . implode( ' ', $col_array ) . ' method-gallery-item-wrap method-gallery-grid-item-wrap">
                                    ' . $this->build_item( $key, 'grid' ) . '
                                </div>
                            ';
                        }
                    }
                    $this->html .= '</div>';
                } else {
                    $this->scripts = '
                        function add_swiper' . $this->id . '() {
                        const swiper' . $this->id . ' = new Swiper(\'.swiper.method-gallery-swiper-' . $this->id . '\', {
                          // Optional parameters
                          loop: true,
                          observer: true,
                          observeSlideChildren: true,
                          observeParents: true,
                          // If we need pagination
                          pagination: {
                            el: \'.method-gallery-swiper-' . $this->id . ' .swiper-pagination\',
                          },

                          // Navigation arrows
                          navigation: {
                            nextEl: \'.method-gallery-swiper-' . $this->id . ' .swiper-button-next\',
                            prevEl: \'.method-gallery-swiper-' . $this->id . ' .swiper-button-prev\',
                          },
                          slidesPerView: 1,
                          breakpoints: {
                            // when window width is >= 480px
                            768: {
                              slidesPerView: ' . ( 2 <= $per_row ? '2' : '1' ) . ',
                              spaceBetween: 30
                            },
                            // when window width is >= 640px
                            1024: {
                              slidesPerView: ' . ( 2 <= $per_row ? $per_row : '1' ) . ',
                              spaceBetween: 40
                            }
                          }
                        });
                        }
                        add_swiper' . $this->id . '();
                    ' . $this->scripts;
                    $this->html .= '<div class="swiper method-gallery-swiper-' . $this->id . '"><div class="swiper-wrapper">';
                    $i = 1;
                    foreach ( $images as $key => $value ) {
                        if ( $this->check_item_visibility( $key ) ) {
                            $this->html .= '
                                <div class="swiper-slide method-gallery-item-wrap method-gallery-swiper-item-wrap">
                                    ' . $this->build_item( $key, 'swiper', ( 1 == $per_row ? true : false ), $i ) . '
                                </div>
                            ';
                            $i++;
                        }
                    }
                    $this->html .= '</div><div class="swiper-pagination"></div><div class="swiper-button-prev"></div><div class="swiper-button-next"></div></div>';
                }
            }
        }
    }

    private function check_item_visibility( $key ) {
        $item_is_visible = false;
        if ( FALSE === get_post_status( $key ) ) {
            // This image no longer exists, so do nothing
        } elseif ( ! empty( $this->active_filter ) ) {
            if ( has_term( (int) $this->active_filter, 'method_gallery_filters', $key ) ) {
                $item_is_visible = true;
            } else {
                // This image does not belong to the active filter, so do nothing
            }
        } else {
            $item_is_visible = true;
        }
        return $item_is_visible;
    }

    private function build_item( $key, $intent, $singleslide = false, $i = 0 ) {
        $item_tag = 'div';
        $show_title = get_post_meta( $key, 'method_gallery_show_title', true );
        $show_desc = get_post_meta( $key, 'method_gallery_show_desc', true );
        $glb_attr = '';
        $glb_desc = '';
        $sw_extra = '';
        if ( ( 'on' == $this->get_meta( '_method_gallery_extra' ) ) && ( 'swiper' == $intent ) && ( $singleslide ) ) {
            if ( ( $show_title ) || ( $show_desc ) ) {
                $sw_extra_title = get_the_title( $key );
                $sw_extra_desc = $this->filter_content( get_the_content( null, false, $key ) );
                if ( ( ! empty( $sw_extra_title ) ) || ( ! empty( $sw_extra_desc ) ) ) {
                    $sw_extra = '
                        <div class="method-gallery-swiper-content">
                            ' . ( $show_title ? ( ! empty( $sw_extra_title ) ? '<h6>' . $sw_extra_title . '</h6>' : '' ) : '' ) . '
                            ' . ( $show_desc ? ( ! empty( $sw_extra_desc ) ? '<div class="method-gallery-copy">' . $sw_extra_desc . '</div>' : '' ) : '' ) . '
                        </div>
                    ';
                }
            }
        }
        if ( 'on' == $this->get_meta( '_method_gallery_lightbox' ) ) {
            $item_tag = 'a';
            if ( $show_title ) {
                $glb_attr .= 'title: ' . esc_attr( get_the_title( $key ) ) . ( $show_desc ? '; ' : '' );
            }
            if ( $show_desc ) {
                $glb_attr .= 'description: .method-gallery-' . $this->id . '-custom-desc-' . $key;
                $glb_desc = '
                    <div class="glightbox-desc method-gallery-' . $this->id . '-custom-desc-' . $key . '">
                        ' . $this->filter_content( get_the_content( null, false, $key ) ) . '
                    </div>
                ';
            }
        }
        $output = '
            <' . $item_tag . ' class="method-gallery-item method-gallery-' . $intent . '-item method-gallery-aspect-ratio method-gallery-aspect-ratio-' . ( $this->get_meta( '_method_gallery_display_' . $intent . '_aspect' ) ? $this->get_meta( '_method_gallery_display_' . $intent . '_aspect' ) : $this->get_option( $intent . '_aspect', '1:1' ) ) . ( 'on' == $this->get_meta( '_method_gallery_lightbox' ) ? ' glightbox glightbox' . $this->id . '" data-gallery="gallery' . $this->id . ( 'swiper' == $intent ? '-' . $i : '' ) . '" href="' . wp_get_attachment_image_url( $key, $this->sizes["lightbox"] ) .'"' . ( ! empty( $glb_attr ) ? ' data-glightbox="' . $glb_attr . '"' : '' ) : '"' ) . '>
                 ' . wp_get_attachment_image( $key, $this->sizes["{$intent}"], false, array( 'class' => 'method-gallery-img' ) ) . '
                 ' . $sw_extra . '
            </' . $item_tag . '>
            ' . ( 'on' == $this->get_meta( '_method_gallery_lightbox' ) ? $glb_desc : '' ) . '
        ';

        return $output;
    }

    private function check_sizes() {
        $this->rsizes = get_intermediate_image_sizes();
        $this->sizes = array(
            'swiper' => ( $this->has_image_size( $this->get_meta( '_method_gallery_display_swiper_size', '' ) ) ? $this->get_meta( '_method_gallery_display_swiper_size' ) : ( $this->has_image_size( $this->get_option( 'swiper_size', '' ) ) ? $this->get_option( 'swiper_size' ) : 'large' ) ),
            'grid' => ( $this->has_image_size( $this->get_meta( '_method_gallery_display_grid_size', '' ) ) ? $this->get_meta( '_method_gallery_display_grid_size' ) : ( $this->has_image_size( $this->get_option( 'grid_size', '' ) ) ? $this->get_option( 'grid_size' ) : 'large' ) ),
            'lightbox' => ( $this->has_image_size( $this->get_option( 'lightbox_size', '' ) ) ? $this->get_option( 'lightbox_size' ) : 'full' ),
        );
    }

    private function has_image_size( $value ) {
        $output = false;
        if ( ! empty( $value ) ) {
            if ( in_array( $value, $this->rsizes ) ) {
                $output = true;
            }
        }
        return $output;
    }

    private function check_filters() {
        if ( 'on' == $this->get_meta( '_method_gallery_filter_enable' ) ) {
            $filters = $this->get_serialized_meta( '_method_gallery_filter_items' );
            if ( $this->check_array_key( $filters, 0 ) ) {
                foreach( $filters as $filter ) {
                    $term = term_exists( (int) $filter );
                    if ( $term !== 0 && $term !== null ) {
                        $this->filters[] = $filter;
                    }
                }
                if ( $this->check_array_key( $_GET, 'mg' . $this->id . '-filter' ) ) {
                    if ( in_array( $_GET["mg{$this->id}-filter"], $this->filters ) ) {
                        $this->active_filter = $_GET["mg{$this->id}-filter"];
                    }
                }
            }
        }
    }

    //======================================================================
    // UTILITY METHODS FROM METHOD v1.3.9
    //======================================================================

    //-----------------------------------------------------
    // Get data for a meta key (current post)
    //-----------------------------------------------------

    private function get_meta( $key, $fallback = '' ) {
        $output = false;
        if ( $this->check_array_key( $this->meta, $key ) ) {
            if ( $this->check_array_key( $this->meta[ "{$key}" ], 0 ) ) {
                $output = $this->meta[ "{$key}" ][0];
            }
        }
        return ( false === $output ? ( ! empty( $fallback ) ? $fallback : false ) : $output );
    }

    //-----------------------------------------------------
    // Get unserialized data for a serialized meta key (current post)
    //-----------------------------------------------------

    private function get_serialized_meta( $key ) {
        $output = false;
        if ( $this->check_array_key( $this->meta, $key ) ) {
            if ( $this->check_array_key( $this->meta[ "{$key}" ], 0 ) ) {
                $output = maybe_unserialize( $this->meta[ "{$key}" ][0] );
            }
        }
        return $output;
    }

    //-----------------------------------------------------
    // Get an option from retrieved plugin options
    //-----------------------------------------------------

    public function get_option( $key, $fallback = '' ) {
        $output = false;
        if ( $this->check_array_key( $this->opts, $key ) ) {
            $output = $this->opts[ "{$key}" ];
        }
        return ( false === $output ? ( ! empty( $fallback ) ? $fallback : false ) : $output );
    }

    //-----------------------------------------------------
    // Check to see if an array key exists, more cleanly.
    //-----------------------------------------------------

    private function check_array_key( $item, $key ) {
        $output = false;
        if ( is_array( $item ) ) {
            if ( array_key_exists( $key, $item ) ) {
                if ( ! empty( $item["{$key}"] ) ) {
                    $output = true;
                }
            }
        }
        return $output;
    }

    //-----------------------------------------------------
    // Run a string through WordPress' content filter
    //-----------------------------------------------------

    public function filter_content( $content ) {
        if ( ! empty( $content ) ) {
            $content = apply_filters( 'the_content', $content );
        }
        return $content;
    }
}

function method_gallery_rebuild_body_function() {
    $output = '';
    if ( ( array_key_exists( 'active', $_POST ) ) && ( array_key_exists( 'gid', $_POST ) ) ) {
        $gallery = new Method_Gallery_v2;
        $output = $gallery->rebuild_gallery( esc_attr( $_POST['gid'] ), esc_attr( $_POST['active'] ) );
    }
    echo $output;
    wp_die();
}

add_action('wp_ajax_method_gallery_rebuild_body', 'method_gallery_rebuild_body_function'); 
add_action('wp_ajax_nopriv_method_gallery_rebuild_body', 'method_gallery_rebuild_body_function');


add_action( 'admin_footer', 'method_gallery_editor_scripts' );

function method_gallery_editor_scripts( $data ) {
    global $pagenow;
    if ( 'post.php' === $pagenow && isset($_GET['post']) && 'method_gallery' === get_post_type( $_GET['post'] ) ) {
        echo '
            <style>
                .method-gallery-display-options-wrap {
                    border: 1px solid #DBDBDB;
                    background: #F6F6F6;
                    padding: 18px;
                    border-radius: 12px;
                }
                .method-gallery-display-options-wrap .cmb-row {
                    padding-top: 1.2em;
                    border-bottom: 1px solid #DBDBDB !important;
                }
                .method-gallery-display-options-wrap .cmb-row:first-of-type {
                    padding-top: 6px;
                }
                .method-gallery-display-options-wrap .cmb-row:last-of-type {
                    border-bottom: none !important;
                    padding-bottom: 0 !important;
                    margin-bottom: 0 !important;
                }
                .cmb-row-flush-bottom {
                    margin-bottom: 0 !important;
                    border-bottom: none !important;
                }
                #cmb2-metabox-method_gallery_shortcode_metabox .cmb2-id--method-gallery-shortcode-info {
                    padding-bottom: 0 !important;
                }
                #cmb2-metabox-method_gallery_shortcode_metabox .cmb2-metabox-description {
                    padding: 0 !important;
                }
            </style>
            <script>
                jQuery( document ).ready(function() {
                    function mgDisplayVisibility() {
                        selected = jQuery(\'input[name="_method_gallery_format"]:checked\').val();
                        if (selected == "swiper") {
                            jQuery("#method-gallery-swiper-options-wrap").css("display", "block");
                            jQuery("#method-gallery-swiper-options-wrap").css("visibility", "visible");
                            jQuery("#method-gallery-grid-options-wrap").css("display", "none");
                            jQuery("#method-gallery-grid-options-wrap").css("visibility", "hidden");
                        } else {
                            jQuery("#method-gallery-swiper-options-wrap").css("display", "none");
                            jQuery("#method-gallery-swiper-options-wrap").css("visibility", "hidden");
                            jQuery("#method-gallery-grid-options-wrap").css("display", "block");
                            jQuery("#method-gallery-grid-options-wrap").css("visibility", "visible");
                        }
                    }
                    mgDisplayVisibility();
                    jQuery(\'input[type="radio"][name="_method_gallery_format"]\').change(function() {
                        mgDisplayVisibility();
                    });
                });
            </script>
        ';
    }
    return $data;
}

