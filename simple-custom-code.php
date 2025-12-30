<?php

/**
 * Plugin Name: Simple Custom Code - CSS, JS, and HTML
 * Plugin URI: https://simplecustomcode.com
 * Description: A comprehensive plugin for managing custom CSS, JavaScript, and HTML code snippets with advanced loading options, conditions, and caching.
 * Version: 1.2
 * Author: SimpleCustomCode Team
 * Author URI: https://simplecustomcode.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simple-custom-code
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.9
 * Requires PHP: 7.0
 *
 * @package SimpleCustomCode
 */

// Prevent direct access
if (! defined('ABSPATH')) {
    exit;
}

if (defined('SCC_PRO_VERSION')) {
    add_action('admin_notices', 'scc_pro_installed_notice');
    function scc_pro_installed_notice()
    {
        $screen = get_current_screen();

        if (! $screen || ! in_array($screen->id, array('edit-simple-custom-code', 'simple-custom-code_page_scc-settings'))) {
            return;
        }

        echo '<div class="notice notice-info">';
        echo '<p>';
        echo '<strong>' . esc_html__('Simple Custom Code Pro is installed and active.', 'simple-custom-code') . '</strong> ';
        echo esc_html__('You can now disable the free Simple Custom Code plugin from your Plugins list, since the Pro version is already running.', 'simple-custom-code');
        echo '</p>';
        echo '</div>';
    }
    return;
}

if (!class_exists('Simple_Custom_Code')) {
    // Define plugin constants
    define('SCC_VERSION', '1.2');
    define('SCC_PLUGIN_FILE', __FILE__);
    define('SCC_PLUGIN_URL', plugin_dir_url(__FILE__));
    define('SCC_PLUGIN_PATH', plugin_dir_path(__FILE__));
    define('SCC_PLUGIN_BASENAME', plugin_basename(__FILE__));

    /**
     * Main plugin class
     */
    class Simple_Custom_Code
    {

        /**
         * Plugin instance
         *
         * @var Simple_Custom_Code
         */
        private static $instance = null;

        /**
         * Get plugin instance
         *
         * @return Simple_Custom_Code
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

            $this->init();
            add_action('admin_init', array($this, 'remove_menu_link'));

            register_activation_hook(__FILE__, array($this, 'activate'));
            register_deactivation_hook(__FILE__, array($this, 'deactivate'));

            add_action('plugins_loaded', array($this, 'register_autoupdate'), 9);
        }

        /**
         * Initialize plugin
         */
        public function init()
        {
            $this->includes();
            $this->init_hooks();
        }

        public function register_autoupdate()
        {
            if (is_admin()) {
                include(SCC_PLUGIN_PATH . 'includes/autoupdate/plugin-update-checker.php');

                $update_url = 'https://update.creoworx.com/simple-custom-code/?action=get_metadata&slug=simple-custom-code';

                // Use the fully qualified class name directly instead of 'use' statement
                $essb_autoupdate = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker($update_url, __FILE__, 'simple-custom-code');
            }
        }

        public function remove_menu_link()
        {
            global $menu;

            $current_settings = scc_get_settings();
            $access_role = isset($current_settings['access_role']) ? $current_settings['access_role'] : 'manage_options';

            if (! is_array($menu) || count($menu) == 0) {
                return false;
            }

            if (current_user_can('activate_plugins') || current_user_can($access_role)) {
                return false;
            }

            remove_menu_page('edit.php?post_type=simple-custom-code');
        }

        /**
         * Include required files
         */
        private function includes()
        {
            if (!class_exists('SCC_Post_Type')) {
                require_once SCC_PLUGIN_PATH . 'includes/class-scc-post-type.php';
                require_once SCC_PLUGIN_PATH . 'includes/class-scc-meta-boxes.php';
                require_once SCC_PLUGIN_PATH . 'includes/class-scc-admin-columns.php';
                require_once SCC_PLUGIN_PATH . 'includes/class-scc-settings.php';
                require_once SCC_PLUGIN_PATH . 'includes/class-scc-frontend.php';
                require_once SCC_PLUGIN_PATH . 'includes/class-scc-file-manager.php';
                require_once SCC_PLUGIN_PATH . 'includes/class-scc-cache.php';
                require_once SCC_PLUGIN_PATH . 'includes/class-scc-chatgpt-code-generator.php';

                
                $settings = scc_get_settings();
                if ($settings['css_customizer']) {
                    require_once SCC_PLUGIN_PATH . 'includes/class-scc-customizer-menu.php';
                    require_once SCC_PLUGIN_PATH . 'includes/class-scc-customizer.php';
                }
            }
        }

        /**
         * Initialize hooks
         */
        private function init_hooks()
        {
            SCC_Post_Type::get_instance();
            SCC_Meta_Boxes::get_instance();
            SCC_Admin_Columns::get_instance();
            SCC_Settings::get_instance();
            SCC_Frontend::get_instance();
            SCC_File_Manager::get_instance();
            SCC_Cache::get_instance();
        }

        /**
         * Plugin activation
         */
        public function activate()
        {
            // Create upload directory
            $upload_dir = wp_upload_dir();
            $scc_dir = $upload_dir['basedir'] . '/simple-custom-code/';
            $cache_dir = $scc_dir . 'cache/';

            if (! file_exists($scc_dir)) {
                wp_mkdir_p($scc_dir);
            }

            if (! file_exists($cache_dir)) {
                wp_mkdir_p($cache_dir);
            }

            // Flush rewrite rules
            flush_rewrite_rules();

            // Set default options
            $default_options = array(
                'editor_template' => 'default',
                'access_role' => 'administrator',
                'cache_mode' => false,
                'cache_type' => 'css_js',
                'cache_method' => 'file',
                'code_linting' => false,
                'code_autocomplete' => false
            );

            add_option('scc_settings', $default_options);
        }

        /**
         * Plugin deactivation
         */
        public function deactivate()
        {
            flush_rewrite_rules();
        }
    }
}

// Helper functions

if (!function_exists('scc_get_settings')) {
    /**
     * Get plugin settings
     *
     * @return array
     */
    function scc_get_settings()
    {
        $defaults = array(
            'editor_template' => 'default',
            'access_role' => 'manage_options',
            'cache_mode' => false,
            'cache_type' => 'css_js',
            'cache_optimized' => 'no',
            'cache_method' => 'file',
            'code_linting' => false,
            'code_disable_debug_comments' => false,
            'code_autocomplete' => false,
            'file_version' => false,
            'css_customizer' => true,
            'ai_model' => 'gpt-4.1',
            'ai_key' => ''
        );

        return wp_parse_args(get_option('scc_settings', array()), $defaults);
    }
}

if (!function_exists('scc_get_upload_dir')) {

    /**
     * Get upload directory for custom codes
     *
     * @return string
     */
    function scc_get_upload_dir()
    {
        $upload_dir = wp_upload_dir();
        return $upload_dir['basedir'] . '/simple-custom-code/';
    }
}

if (!function_exists('scc_get_upload_url')) {

    /**
     * Get upload URL for custom codes
     *
     * @return string
     */
    function scc_get_upload_url()
    {
        $upload_dir = wp_upload_dir();
        return $upload_dir['baseurl'] . '/simple-custom-code/';
    }
}

if (!function_exists('scc_user_has_access')) {
    /**
     * Check if user has access to plugin
     *
     * @return bool
     */
    function scc_user_has_access()
    {
        $settings = scc_get_settings();
        return current_user_can($settings['access_role']);
    }
}

if (!function_exists('scc_get_code_types')) {
    /**
     * Get available code types
     *
     * @return array
     */
    function scc_get_code_types()
    {
        return array(
            'css' => esc_html__('CSS', 'simple-custom-code'),
            'js' => esc_html__('JavaScript', 'simple-custom-code'),
            'html' => esc_html__('HTML', 'simple-custom-code')
        );
    }
}

if (!function_exists('scc_get_loading_methods')) {
    /**
     * Get available loading methods
     *
     * @return array
     */
    function scc_get_loading_methods()
    {
        return array(
            'automatic' => esc_html__('Automatically', 'simple-custom-code'),
            'manual' => esc_html__('Manually', 'simple-custom-code')
        );
    }
}

if (!function_exists('scc_get_manual_types')) {
    /**
     * Get manual loading types
     *
     * @return array
     */
    function scc_get_manual_types()
    {
        return array(
            'shortcode' => esc_html__('Shortcode', 'simple-custom-code'),
            'action' => esc_html__('Action Hook', 'simple-custom-code'),
            'filter' => esc_html__('Filter Hook', 'simple-custom-code')
        );
    }
}

if (!function_exists('scc_get_loading_locations')) {
    /**
     * Get loading locations
     *
     * @return array
     */
    function scc_get_loading_locations()
    {
        return array(
            'frontend' => esc_html__('Frontend (Site)', 'simple-custom-code'),
            'backend' => esc_html__('Backend (Admin)', 'simple-custom-code'),
            'login' => esc_html__('Login Page', 'simple-custom-code'),
            'block_editor' => esc_html__('Block Editor', 'simple-custom-code')
        );
    }
}

if (!function_exists('scc_get_code_positions')) {
    /**
     * Get code positions
     *
     * @return array
     */
    function scc_get_code_positions()
    {
        return array(
            'head' => esc_html__('Head', 'simple-custom-code'),
            'body_open' => esc_html__('After <body> tag', 'simple-custom-code'),
            'footer' => esc_html__('End of Page', 'simple-custom-code')
        );
    }
}

if (!function_exists('scc_get_linking_types')) {
    /**
     * Get linking types
     *
     * @return array
     */
    function scc_get_linking_types()
    {
        return array(
            'internal' => esc_html__('Internal', 'simple-custom-code'),
            'external' => esc_html__('External File', 'simple-custom-code')
        );
    }
}

if (!function_exists('scc_get_css_preprocessors')) {
    /**
     * Get CSS preprocessors
     *
     * @return array
     */
    function scc_get_css_preprocessors()
    {
        return array(
            'none' => esc_html__('None', 'simple-custom-code'),
            'scss' => esc_html__('SCSS', 'simple-custom-code'),
            'less' => esc_html__('Less', 'simple-custom-code')
        );
    }
}

if (!function_exists('scc_get_condition_operators')) {
    /**
     * Get condition operators
     *
     * @return array
     */
    function scc_get_condition_operators()
    {
        return array(
            'contains' => esc_html__('Contains', 'simple-custom-code'),
            'not_contains' => esc_html__('Not Contains', 'simple-custom-code'),
            'equals' => esc_html__('Equals', 'simple-custom-code'),
            'not_equals' => esc_html__('Not Equals', 'simple-custom-code'),
            'starts_with' => esc_html__('Starts With', 'simple-custom-code'),
            'ends_with' => esc_html__('Ends With', 'simple-custom-code'),
            'regex' => esc_html__('Regular Expression', 'simple-custom-code')
        );
    }
}

if (!function_exists('scc_get_priority_options')) {
    /**
     * Get priority options
     *
     * @return array
     */
    function scc_get_priority_options()
    {
        $options = array();
        for ($i = 0; $i <= 15; $i++) {
            $options[$i] = $i . ($i === 0 ? esc_html__(' (Highest)', 'simple-custom-code') : ($i === 15 ? esc_html__(' (Lowest)', 'simple-custom-code') : ''));
        }
        return $options;
    }
}


if (!function_exists('scc_get_pro_url')) {
    function scc_get_pro_url()
    {
        return 'https://simplecustomcode.com/pricing/';
    }
}

if (!function_exists('scc_additional_settings_links')) {

    function scc_additional_settings_links($links)
    {

        $settings_link = '<a href="edit.php?post_type=simple-custom-code&page=scc-settings">' .
            esc_html(__('Settings', 'simple-custom-code')) . '</a>';

        $pro_link = '<a href="' . scc_get_pro_url() . '" target="_blank">' .
            esc_html(__('Upgrade to Pro', 'simple-custom-code')) . '</a>';

        return array_merge(array($settings_link, $pro_link), $links);
    }

    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'scc_additional_settings_links');
}



if (!function_exists('scc_init')) {
    /**
     * Initialize the plugin
     */
    function scc_init()
    {
        return Simple_Custom_Code::get_instance();
    }

    // Start the plugin
    scc_init();
}
