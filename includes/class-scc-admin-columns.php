<?php

/**
 * Admin columns functionality
 *
 * @package CustomCodeManager
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * SCC_Admin_Columns class
 */
class SCC_Admin_Columns
{

    /**
     * Instance of this class
     *
     * @var SCC_Admin_Columns
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return SCC_Admin_Columns
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
        add_filter('manage_simple-custom-code_posts_columns', array($this, 'add_custom_columns'));
        add_action('manage_simple-custom-code_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
        add_filter('manage_edit-simple-custom-code_sortable_columns', array($this, 'sortable_columns'));
        add_action('pre_get_posts', array($this, 'sort_custom_columns'));
        add_action('restrict_manage_posts', array($this, 'add_filter_dropdowns'));
        add_action('parse_query', array($this, 'filter_posts_by_meta'));
        add_action('wp_ajax_scc_toggle_active', array($this, 'ajax_toggle_active'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Add custom columns to post list table
     */
    public function add_custom_columns($columns)
    {
        // Remove default columns we don't need
        unset($columns['date']);

        // Add our custom columns
        $custom_columns = array(
            'cb' => '<input id="cb-select-all-1" type="checkbox"><label for="cb-select-all-1"><span class="screen-reader-text">Select All</span></label>',
            'scc_code_type' => __('Type', 'simple-custom-code'),
            'scc_active' => __('Status', 'simple-custom-code'),
            'title' => __('Title', 'simple-custom-code'),
            'scc_details' => __('Details', 'simple-custom-code'),
            'author' => __('Author', 'simple-custom-code'),
            'scc_date' => __('Date', 'simple-custom-code'),
            'scc_modified' => __('Modified', 'simple-custom-code')
        );



        //return array_merge($columns, $custom_columns);
        return $custom_columns;
    }

    /**
     * Display custom column content
     */
    public function custom_column_content($column, $post_id)
    {
        switch ($column) {
            case 'scc_active':
                $active = get_post_meta($post_id, '_scc_active', true);
                $active = $active !== false ? (bool) $active : true;

                //$toggle_url = wp_nonce_url(
                //    admin_url( 'admin-ajax.php?action=scc_toggle_active&post_id=' . $post_id ),
                //    'scc_toggle_active_' . $post_id
                //    );

                $post_scc_nonce = wp_create_nonce('scc_toggle_active_' . $post_id);

                echo '<div class="scc-status-toggle" data-post-id="' . esc_attr($post_id) . '" data-nonce="' . esc_attr($post_scc_nonce) . '">';
                if ($active) {
                    echo '<span class="scc-status scc-status-active" title="' . esc_attr__('Click to deactivate', 'simple-custom-code') . '">';
                    echo '<span class="dashicons dashicons-yes-alt"></span> ' . esc_html__('Active', 'simple-custom-code');
                    echo '</span>';
                } else {
                    echo '<span class="scc-status scc-status-inactive" title="' . esc_attr__('Click to activate', 'simple-custom-code') . '">';
                    echo '<span class="dashicons dashicons-dismiss"></span> ' . esc_html__('Inactive', 'simple-custom-code');
                    echo '</span>';
                }
                echo '</div>';
                break;

            case 'scc_code_type':
                $code_type = get_post_meta($post_id, '_scc_code_type', true);
                $types = scc_get_code_types();

                if ($code_type && isset($types[$code_type])) {
                    $type_class = 'scc-type-' . $code_type;
                    echo '<span class="scc-code-type ' . esc_attr($type_class) . '">' . esc_html($code_type) . '</span>';
                } else {
                    echo '�';
                }
                break;

            case 'scc_details':
                echo '<div class="scc-details-column">';

                // Loading method with icon
                $loading_method = get_post_meta($post_id, '_scc_loading_method', true);
                $methods = scc_get_loading_methods();

                if ($loading_method && isset($methods[$loading_method])) {
                    $loading_icon = $loading_method === 'automatic' ? 'dashicons-update-alt' : 'dashicons-admin-tools';
                    echo '<div class="scc-detail-item">';
                    echo '<span class="dashicons ' . esc_attr($loading_icon) . '" title="' . esc_attr($methods[$loading_method]) . '"></span>';
                    echo '<span class="scc-detail-text">' . esc_html($methods[$loading_method]);

                    // Show manual type if applicable
                    if ($loading_method === 'manual') {
                        $manual_type = get_post_meta($post_id, '_scc_manual_type', true);
                        $manual_types = scc_get_manual_types();

                        if ($manual_type && isset($manual_types[$manual_type])) {
                            echo ' (' . esc_html($manual_types[$manual_type]) . ')';
                        }
                    }
                    echo '</span>';
                    echo '</div>';
                }

                // Locations
                $locations = get_post_meta($post_id, '_scc_loading_locations', true);
                $available_locations = scc_get_loading_locations();

                if (is_array($locations) && ! empty($locations)) {
                    $location_names = array();
                    foreach ($locations as $location) {
                        if (isset($available_locations[$location])) {
                            $location_names[] = $available_locations[$location];
                        }
                    }
                    if (! empty($location_names)) {
                        echo '<div class="scc-detail-item">';
                        echo '<span class="dashicons dashicons-location" title="' . esc_attr__('Loading locations', 'simple-custom-code') . '"></span>';
                        echo '<span class="scc-detail-text">' . esc_html(implode(', ', $location_names)) . '</span>';
                        echo '</div>';
                    }
                }

                // Priority
                $priority = get_post_meta($post_id, '_scc_priority', true);
                $priority = $priority !== false ? (int) $priority : 10;

                $priority_class = '';
                $priority_icon = 'dashicons-sort';
                if ($priority <= 3) {
                    $priority_class = 'scc-priority-high';
                    $priority_icon = 'dashicons-arrow-up-alt';
                } elseif ($priority >= 12) {
                    $priority_class = 'scc-priority-low';
                    $priority_icon = 'dashicons-arrow-down-alt';
                }

                echo '<div class="scc-detail-item ' . esc_attr($priority_class) . '">';
                echo '<span class="dashicons ' . esc_attr($priority_icon) . '" title="' . esc_attr__('Priority', 'simple-custom-code') . '"></span>';
                echo '<span class="scc-detail-text scc-priority">' . esc_html($priority) . '</span>';
                echo '</div>';

                // Options
                $options = array();

                // Minify option
                if (get_post_meta($post_id, '_scc_minify_enabled', true)) {
                    $options[] = '<span class="scc-option-badge" title="' . esc_attr__('Minification enabled', 'simple-custom-code') . '">MIN</span>';
                }

                // CSS Preprocessor
                $code_type = get_post_meta($post_id, '_scc_code_type', true);
                if ($code_type === 'css') {
                    $preprocessor = get_post_meta($post_id, '_scc_css_preprocessor', true);
                    if ($preprocessor && $preprocessor !== 'none') {
                        $options[] = '<span class="scc-option-badge" title="' . esc_attr__('CSS Preprocessor', 'simple-custom-code') . '">' . esc_html(strtoupper($preprocessor)) . '</span>';
                    }
                }

                // External file option
                $linking_type = get_post_meta($post_id, '_scc_linking_type', true);
                if ($linking_type === 'external') {
                    $options[] = '<span class="scc-option-badge" title="' . esc_attr__('External file', 'simple-custom-code') . '">EXT</span>';
                }

                if (! empty($options)) {
                    echo '<div class="scc-detail-item">';
                    echo '<span class="dashicons dashicons-admin-settings" title="' . esc_attr__('Options', 'simple-custom-code') . '"></span>';
                    echo '<span class="scc-detail-text">' . implode(' ', $options) . '</span>';
                    echo '</div>';
                }

                echo '</div>';
                break;

            case 'scc_modified':
                $modified = get_the_modified_time('U', $post_id);
                $current_time = current_time('timestamp');
                $time_diff = human_time_diff($modified, $current_time);
                $time_diff_value = $current_time - $modified;

                echo '<span title="' . esc_attr(get_the_modified_date('Y/m/d g:i:s a', $post_id)) . '">';
                if ($time_diff_value < DAY_IN_SECONDS) {
                    echo esc_html( $time_diff . ' ' . esc_html__('ago', 'simple-custom-code'));
                }
                else {
                    echo get_the_modified_date('Y/m/d', $post_id);
                }
                echo '</span>';
                break;

            case 'scc_date': 
                $modified = get_the_time('U', $post_id);
                $current_time = current_time('timestamp');
                $time_diff = human_time_diff($modified, $current_time);
                $time_diff_value = $current_time - $modified;

                echo '<span title="' . esc_attr(get_the_date('Y/m/d g:i:s a', $post_id)) . '">';
                if ($time_diff_value < DAY_IN_SECONDS) {
                    echo esc_html( $time_diff . ' ' . esc_html__('ago', 'simple-custom-code'));
                }
                else {
                    echo get_the_date('Y/m/d', $post_id);
                }
                echo '</span>';
                break;
        }
    }

    /**
     * Make columns sortable
     */
    public function sortable_columns($columns)
    {
        $columns['scc_code_type'] = 'scc_code_type';
        $columns['scc_active'] = 'scc_active';
        $columns['scc_modified'] = 'scc_modified';
        $columns['scc_date'] = 'scc_date';

        return $columns;
    }

    /**
     * Handle custom column sorting
     */
    public function sort_custom_columns($query)
    {
        if (! is_admin() || ! $query->is_main_query()) {
            return;
        }

        $orderby = $query->get('orderby');

        if (in_array($orderby, array('scc_code_type', 'scc_active'))) {
            $query->set('meta_key', '_' . $orderby);
            $query->set('orderby', 'meta_value');
        }
    }

    /**
     * Add filter dropdowns to post list table
     */
    public function add_filter_dropdowns()
    {
        global $typenow;

        if ($typenow !== 'simple-custom-code') {
            return;
        }

        // Code type filter
        $selected_type = isset($_GET['scc_code_type']) ? sanitize_text_field(wp_unslash($_GET['scc_code_type'])) : '';
        echo '<select name="scc_code_type">';
        echo '<option value="">' . esc_html__('All Types', 'simple-custom-code') . '</option>';
        foreach (scc_get_code_types() as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($selected_type, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';


        // Status filter
        $selected_status = isset($_GET['scc_active']) ? sanitize_text_field(wp_unslash($_GET['scc_active'])) : '';
        echo '<select name="scc_active">';
        echo '<option value="">' . esc_html__('All Status', 'simple-custom-code') . '</option>';
        echo '<option value="1" ' . selected($selected_status, '1', false) . '>' . esc_html__('Active', 'simple-custom-code') . '</option>';
        echo '<option value="0" ' . selected($selected_status, '0', false) . '>' . esc_html__('Inactive', 'simple-custom-code') . '</option>';
        echo '</select>';
    }

    /**
     * Filter posts by custom meta values
     */
    public function filter_posts_by_meta($query)
    {
        global $pagenow, $typenow;

        if ($pagenow !== 'edit.php' || $typenow !== 'simple-custom-code' || ! is_admin()) {
            return;
        }

        $meta_query = array();

        // Filter by code type
        if (! empty($_GET['scc_code_type'])) {
            $meta_query[] = array(
                'key' => '_scc_code_type',
                'value' => sanitize_text_field(wp_unslash($_GET['scc_code_type'])),
                'compare' => '='
            );
        }

        // Filter by loading method
        if (! empty($_GET['scc_loading_method'])) {
            $meta_query[] = array(
                'key' => '_scc_loading_method',
                'value' => sanitize_text_field(wp_unslash($_GET['scc_loading_method'])),
                'compare' => '='
            );
        }

        // Filter by active status
        if (isset($_GET['scc_active']) && $_GET['scc_active'] !== '') {
            $meta_query[] = array(
                'key' => '_scc_active',
                'value' => $_GET['scc_active'] === '1' ? '1' : '0',
                'compare' => '='
            );
        }

        if (! empty($meta_query)) {
            $query->set('meta_query', $meta_query);
        }
    }

    /**
     * AJAX handler to toggle active status
     */
    public function ajax_toggle_active()
    {
        $post_id = (int) $_REQUEST['post_id'];

        if (! wp_verify_nonce($_REQUEST['_nonce'], 'scc_toggle_active_' . $post_id)) {
            wp_die(esc_html__('Invalid nonce', 'simple-custom-code') . '-' . $_REQUEST['_nonce']);
        }

        if (! current_user_can('edit_post', $post_id)) {
            wp_die(esc_html__('You do not have permission to edit this post.', 'simple-custom-code'));
        }

        $current_status = get_post_meta($post_id, '_scc_active', true);
        $current_status = $current_status !== false ? (bool) $current_status : true;
        $new_status = ! $current_status;

        update_post_meta($post_id, '_scc_active', $new_status);

        // Clear cache when status changes
        $cache = SCC_Cache::get_instance();
        $cache->clear_all_cache();

        $response = array(
            'success' => true,
            'new_status' => $new_status,
            'code' => $this->get_status_html($post_id, $new_status)
        );

        wp_send_json($response);
    }

    /**
     * Get status HTML for AJAX response
     */
    private function get_status_html($post_id, $active)
    {
        ob_start();

        $post_scc_nonce = wp_create_nonce('scc_toggle_active_' . $post_id);


        echo '<div class="scc-status-toggle" data-post-id="' . esc_attr($post_id) . '" data-nonce="' . esc_attr($post_scc_nonce) . '">';
        if ($active) {
            echo '<span class="scc-status scc-status-active" title="' . esc_attr__('Click to deactivate', 'simple-custom-code') . '">';
            echo '<span class="dashicons dashicons-yes-alt"></span> ' . esc_html__('Active', 'simple-custom-code');
            echo '</span>';
        } else {
            echo '<span class="scc-status scc-status-inactive" title="' . esc_attr__('Click to activate', 'simple-custom-code') . '">';
            echo '<span class="dashicons dashicons-dismiss"></span> ' . esc_html__('Inactive', 'simple-custom-code');
            echo '</span>';
        }
        echo '</div>';

        return ob_get_clean();
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook)
    {
        global $post_type;

        if ($post_type !== 'simple-custom-code' || $hook !== 'edit.php') {
            return;
        }

        wp_enqueue_script(
            'scc-admin-list',
            SCC_PLUGIN_URL . 'assets/js/admin-list.js',
            array('jquery'),
            SCC_VERSION,
            true
        );

        wp_localize_script(
            'scc-admin-list',
            'ccmAdminList',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'strings' => array(
                    'confirm_toggle' => esc_html__('Are you sure you want to change the status of this code?', 'simple-custom-code'),
                    'error' => esc_html__('An error occurred. Please try again.', 'simple-custom-code')
                )
            )
        );

        wp_enqueue_style(
            'scc-admin-list',
            SCC_PLUGIN_URL . 'assets/css/admin-list.css',
            array(),
            SCC_VERSION
        );
    }
}
