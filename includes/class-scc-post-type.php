<?php

/**
 * Custom post type and taxonomy registration
 *
 * @package CustomCodeManager
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * SCC_Post_Type class
 */
class SCC_Post_Type
{

    /**
     * Instance of this class
     *
     * @var SCC_Post_Type
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return SCC_Post_Type
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomy'));
        add_filter('post_updated_messages', array($this, 'updated_messages'));
    }

    /**
     * Register custom post type
     */
    public function register_post_type()
    {
        $labels = array(
            'name'                  => _x('Custom Codes', 'Post type general name', 'simple-custom-code'),
            'singular_name'         => _x('Custom Code', 'Post type singular name', 'simple-custom-code'),
            'menu_name'             => _x('Custom Codes', 'Admin Menu text', 'simple-custom-code'),
            'name_admin_bar'        => _x('Custom Code', 'Add New on Toolbar', 'simple-custom-code'),
            'add_new'               => __('Add New', 'simple-custom-code'),
            'add_new_item'          => __('Add New Custom Code', 'simple-custom-code'),
            'new_item'              => __('New Custom Code', 'simple-custom-code'),
            'edit_item'             => __('Edit Custom Code', 'simple-custom-code'),
            'view_item'             => __('View Custom Code', 'simple-custom-code'),
            'all_items'             => __('All Custom Codes', 'simple-custom-code'),
            'search_items'          => __('Search Custom Codes', 'simple-custom-code'),
            'parent_item_colon'     => __('Parent Custom Codes:', 'simple-custom-code'),
            'not_found'             => __('No custom codes found.', 'simple-custom-code'),
            'not_found_in_trash'    => __('No custom codes found in Trash.', 'simple-custom-code'),
            'featured_image'        => _x('Custom Code Featured Image', 'Overrides the "Featured Image" phrase', 'simple-custom-code'),
            'set_featured_image'    => _x('Set featured image', 'Overrides the "Set featured image" phrase', 'simple-custom-code'),
            'remove_featured_image' => _x('Remove featured image', 'Overrides the "Remove featured image" phrase', 'simple-custom-code'),
            'use_featured_image'    => _x('Use as featured image', 'Overrides the "Use as featured image" phrase', 'simple-custom-code'),
            'archives'              => _x('Custom Code archives', 'The post type archive label', 'simple-custom-code'),
            'insert_into_item'      => _x('Insert into custom code', 'Overrides the "Insert into post" phrase', 'simple-custom-code'),
            'uploaded_to_this_item' => _x('Uploaded to this custom code', 'Overrides the "Uploaded to this post" phrase', 'simple-custom-code'),
            'filter_items_list'     => _x('Filter custom codes list', 'Screen reader text for the filter links', 'simple-custom-code'),
            'items_list_navigation' => _x('Custom codes list navigation', 'Screen reader text for the pagination', 'simple-custom-code'),
            'items_list'            => _x('Custom codes list', 'Screen reader text for the items list', 'simple-custom-code'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'menu_icon'          => 'dashicons-editor-code',
            'supports'           => array('title'),
            'show_in_rest'       => false,
        );

        register_post_type('simple-custom-code', $args);
    }

    /**
     * Register custom taxonomy
     */
    public function register_taxonomy()
    {
        $labels = array(
            'name'                       => _x('Code Categories', 'Taxonomy General Name', 'simple-custom-code'),
            'singular_name'              => _x('Code Category', 'Taxonomy Singular Name', 'simple-custom-code'),
            'menu_name'                  => __('Categories', 'simple-custom-code'),
            'all_items'                  => __('All Categories', 'simple-custom-code'),
            'parent_item'                => __('Parent Category', 'simple-custom-code'),
            'parent_item_colon'          => __('Parent Category:', 'simple-custom-code'),
            'new_item_name'              => __('New Category Name', 'simple-custom-code'),
            'add_new_item'               => __('Add New Category', 'simple-custom-code'),
            'edit_item'                  => __('Edit Category', 'simple-custom-code'),
            'update_item'                => __('Update Category', 'simple-custom-code'),
            'view_item'                  => __('View Category', 'simple-custom-code'),
            'separate_items_with_commas' => __('Separate categories with commas', 'simple-custom-code'),
            'add_or_remove_items'        => __('Add or remove categories', 'simple-custom-code'),
            'choose_from_most_used'      => __('Choose from the most used', 'simple-custom-code'),
            'popular_items'              => __('Popular Categories', 'simple-custom-code'),
            'search_items'               => __('Search Categories', 'simple-custom-code'),
            'not_found'                  => __('Not Found', 'simple-custom-code'),
            'no_terms'                   => __('No categories', 'simple-custom-code'),
            'items_list'                 => __('Categories list', 'simple-custom-code'),
            'items_list_navigation'      => __('Categories list navigation', 'simple-custom-code'),
        );

        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => true,
            'public'                     => false,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => false,
            'show_tagcloud'              => false,
            'show_in_rest'               => false,
        );

        //register_taxonomy( 'scc_code_category', array( 'simple-custom-code' ), $args );
    }

    /**
     * Custom post updated messages
     *
     * @param array $messages Existing post update messages.
     * @return array Amended post update messages.
     */
    public function updated_messages($messages)
    {
        $post             = get_post();
        $post_type        = get_post_type($post);
        $post_type_object = get_post_type_object($post_type);

        $messages['simple-custom-code'] = array(
            0  => '', // Unused. Messages start at index 1.
            1  => __('Custom code updated.', 'simple-custom-code'),
            2  => __('Custom field updated.', 'simple-custom-code'),
            3  => __('Custom field deleted.', 'simple-custom-code'),
            4  => __('Custom code updated.', 'simple-custom-code'),
            /* translators: %s: date and time of the revision */
            5  => isset($_GET['revision']) ? sprintf(__('Custom code restored to revision from %s', 'simple-custom-code'), wp_post_revision_title((int) $_GET['revision'], false)) : false,
            6  => __('Custom code published.', 'simple-custom-code'),
            7  => __('Custom code saved.', 'simple-custom-code'),
            8  => __('Custom code submitted.', 'simple-custom-code'),
            /* translators: %s: date and time of the revision */
            9  => sprintf(__('Custom code scheduled for: <strong>%1$s</strong>.', 'simple-custom-code'), date_i18n(__('M j, Y @ G:i', 'simple-custom-code'), strtotime($post->post_date))),
            10 => __('Custom code draft updated.', 'simple-custom-code')
        );

        return $messages;
    }
}
