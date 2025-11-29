<?php

/**
 * Frontend functionality
 *
 * @package CustomCodeManager
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * SCC_Frontend class
 */
class SCC_Frontend
{

    /**
     * Instance of this class
     *
     * @var SCC_Frontend
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return SCC_Frontend
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
        add_action('wp_head', array($this, 'output_head_codes'), 30);
        add_action('wp_body_open', array($this, 'output_body_open_codes'), 30);
        add_action('wp_footer', array($this, 'output_footer_codes'), 999);
        add_action('admin_head', array($this, 'output_admin_head_codes'), 30);
        add_action('admin_footer', array($this, 'output_admin_footer_codes'), 999);
        add_action('login_head', array($this, 'output_login_head_codes'), 30);
        add_action('login_footer', array($this, 'output_login_footer_codes'), 999);
        add_action('enqueue_block_editor_assets', array($this, 'output_block_editor_codes'));
    }
    
    public function output_body_open_codes() {
        $this->output_codes('frontend', 'body_open');
    }

    /**
     * Output head codes for frontend
     */
    public function output_head_codes()
    {
        $this->output_codes('frontend', 'head');
    }

    /**
     * Output footer codes for frontend
     */
    public function output_footer_codes()
    {
        $this->output_codes('frontend', 'footer');
    }

    /**
     * Output head codes for admin
     */
    public function output_admin_head_codes()
    {
        $this->output_codes('backend', 'head');
    }

    /**
     * Output footer codes for admin
     */
    public function output_admin_footer_codes()
    {
        $this->output_codes('backend', 'footer');
    }

    /**
     * Output head codes for login page
     */
    public function output_login_head_codes()
    {
        $this->output_codes('login', 'head');
    }

    /**
     * Output footer codes for login page
     */
    public function output_login_footer_codes()
    {
        $this->output_codes('login', 'footer');
    }

    /**
     * Output codes for block editor
     */
    public function output_block_editor_codes()
    {
        $this->output_codes('block_editor', 'head');
    }

    /**
     * Main function to output codes
     *
     * @param string $location Loading location (frontend, backend, login, block_editor).
     * @param string $position Code position (head, footer).
     */
    private function output_codes($location, $position)
    {
        $codes = $this->get_codes_for_location($location, $position);

        if (empty($codes)) {
            return;
        }

        $cache = SCC_Cache::get_instance();
        $current_url = $this->get_current_url();

        // Check if cache is enabled and we have cached content
        if ($cache->is_cache_enabled()) {
            $settings = scc_get_settings();
            $cache_type = $settings['cache_type'];
            $disable_comments = $settings['code_disable_debug_comments'];

            $cached_content = $cache->get_cached_content($current_url, $position, $cache_type);

            if ($cached_content !== false) {
                if (!$disable_comments) {
                    echo "<!-- Simple Custom Code - Cached Content -->\n";
                }
                echo $cached_content;
                return;
            }

            // Generate cache if not exists
            $cache->set_cached_content($current_url, $position, $cache_type, $codes);

            // Try to get cached content again
            $cached_content = $cache->get_cached_content($current_url, $position, $cache_type);
            if ($cached_content !== false) {
                if (!$disable_comments) {
                    echo "<!-- Simple Custom Code - Cached Content -->\n";
                }
                echo $cached_content;
                return;
            }
        }

        // Output codes without cache
        if (!scc_get_settings()['code_disable_debug_comments']) {
            echo "<!-- Simple Custom Code - Direct Output -->\n";
        }
        $this->output_codes_directly($codes);
    }

    /**
     * Get codes for specific location and position
     *
     * @param string $location Loading location.
     * @param string $position Code position.
     * @return array
     */
    private function get_codes_for_location($location, $position)
    {
        $args = array(
            'post_type' => 'simple-custom-code',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_scc_active',
                    'value' => '1',
                    'compare' => '='
                ),
                array(
                    'key' => '_scc_loading_method',
                    'value' => 'automatic',
                    'compare' => '='
                ),
                array(
                    'key' => '_scc_code_position',
                    'value' => $position,
                    'compare' => '='
                ),
                array(
                    'key' => '_scc_loading_locations',
                    'value' => $location,
                    'compare' => 'LIKE'
                )
            ),
            'meta_key' => '_scc_priority',
            'orderby' => 'meta_value_num',
            'order' => 'ASC'
        );

        $posts = get_posts($args);
        $codes = array();

        foreach ($posts as $post) {

            $codes[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'type' => get_post_meta($post->ID, '_scc_code_type', true),
                'linking_type' => get_post_meta($post->ID, '_scc_linking_type', true),
                'css_optimize' => get_post_meta($post->ID, '_scc_css_optimize_enabled', true),
                'js_optimize' => get_post_meta($post->ID, '_scc_js_optimize_enabled', true),
                'priority' => (int) get_post_meta($post->ID, '_scc_priority', true)
            );
        }

        return $codes;
    }

    /**
     * Evaluate a single condition
     *
     * @param string $url Current URL.
     * @param array $condition Condition array.
     * @return bool
     */
    private function evaluate_condition($url, $condition)
    {
        $operator = $condition['operator'];
        $pattern = $condition['url'];

        switch ($operator) {
            case 'contains':
                return strpos($url, $pattern) !== false;

            case 'not_contains':
                return strpos($url, $pattern) === false;

            case 'equals':
                return $url === $pattern;

            case 'not_equals':
                return $url !== $pattern;

            case 'starts_with':
                return strpos($url, $pattern) === 0;

            case 'ends_with':
                return substr($url, -strlen($pattern)) === $pattern;

            case 'regex':
                return preg_match('/' . $pattern . '/', $url);

            default:
                return false;
        }
    }

    /**
     * Output codes directly without cache
     *
     * @param array $codes Array of code data.
     */
    private function output_codes_directly($codes)
    {
        $file_manager = SCC_File_Manager::get_instance();
        $settings = scc_get_settings();

        foreach ($codes as $code) {
            $file_info = $file_manager->get_loading_file_info($code['id']);

            if (!$settings['code_disable_debug_comments']) {
                echo "<!-- Simple Custom Code ID: {" . esc_attr($code['id']) . "} - {" . esc_attr($code['title']) . "} -->\n";
            }

            if ($file_info['linking_type'] === 'external' && $code['type'] !== 'html') {
                // Load as external file
                $this->output_external_code($code, $file_info);
            } else {
                // Load as inline code
                $this->output_inline_code($code, $file_info);
            }

            echo "\n";
        }
    }

    /**
     * Output external code (as file link)
     *
     * @param array $code Code data.
     * @param array $file_info File information.
     */
    private function output_external_code($code, $file_info)
    {
        $settings = scc_get_settings();
        if (! $file_info['exists']) {
            if (!$settings['code_disable_debug_comments']) {
                echo "<!-- Error: Code file not found " . esc_url($file_info['url']) . "-->\n";
            }
            return;
        }

        $file_url = $file_info['url'];

        if ($settings['file_version'] && isset($file_info['modified'])) {
            $file_url .= '?ver=' . $file_info['modified'];
        }

        switch ($code['type']) {
            case 'css':
                $optimize = isset($code['css_optimize']) ? $code['css_optimize'] : 'no';

                if ($optimize == 'yes') {
                    echo '<link rel="preload" href="' . esc_url($file_url) . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'" />';
                } else {
                    echo '<link rel="stylesheet" href="' . esc_url($file_url) . '" type="text/css" media="all" />';
                }
                break;

            case 'js':
                $optimize = isset($code['js_optimize']) ? $code['js_optimize'] : 'no';
                echo '<script src="' . esc_url($file_url) . '"' . ($optimize == 'yes' ? ' defer' : '') . '></script>';
                break;
        }
    }

    /**
     * Output inline code
     *
     * @param array $code Code data.
     * @param array $file_info File information.
     */
    private function output_inline_code($code, $file_info)
    {
        $file_manager = SCC_File_Manager::get_instance();
        $content = $file_manager->get_code_for_output($code['id']);
        $settings = scc_get_settings();

        if (empty($content)) {
            if (!$settings['code_disable_debug_comments']) {
                echo "<!-- Error: No code content found ID: " . esc_attr($code['id']) . "-->\n";
            }
            return;
        }

        switch ($code['type']) {
            case 'css':
                echo '<style type="text/css">' . "\n" . $content . "\n" . '</style>';
                break;

            case 'js':
                echo '<script type="text/javascript">' . "\n" . $content . "\n" . '</script>';
                break;

            case 'html':
                echo $content;
                break;
        }
    }

    /**
     * Get current URL
     *
     * @return string
     */
    private function get_current_url()
    {
        if (is_admin()) {
            return admin_url(basename($_SERVER['REQUEST_URI']));
        }

        if (function_exists('wp_login_url') && strpos($_SERVER['REQUEST_URI'], 'wp-login') !== false) {
            return wp_login_url();
        }

        $protocol = is_ssl() ? 'https://' : 'http://';
        return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    /**
     * Get codes by manual loading type for hooks
     *
     * @param string $manual_type Manual loading type (action, filter).
     * @param string $hook_name Hook name.
     * @return array
     */
    public function get_manual_codes($manual_type, $hook_name)
    {
        $args = array(
            'post_type' => 'simple-custom-code',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_scc_active',
                    'value' => '1',
                    'compare' => '='
                ),
                array(
                    'key' => '_scc_loading_method',
                    'value' => 'manual',
                    'compare' => '='
                ),
                array(
                    'key' => '_scc_manual_type',
                    'value' => $manual_type,
                    'compare' => '='
                ),
                array(
                    'key' => '_scc_action_filter_name',
                    'value' => $hook_name,
                    'compare' => '='
                )
            ),
            'meta_key' => '_scc_priority',
            'orderby' => 'meta_value_num',
            'order' => 'ASC'
        );

        return get_posts($args);
    }

    /**
     * Execute manual code by ID
     *
     * @param int $post_id Post ID.
     * @param array $args Additional arguments.
     * @return mixed
     */
    public function execute_manual_code($post_id, $args = array())
    {
        $code_type = get_post_meta($post_id, '_scc_code_type', true);

        if ($code_type !== 'js' && $code_type !== 'html') {
            return false;
        }

        $file_manager = SCC_File_Manager::get_instance();
        $content = $file_manager->get_code_for_output($post_id);

        if (empty($content)) {
            return false;
        }

        if ($code_type === 'js') {
            // For JavaScript, we need to output it wrapped in script tags
            echo '<script type="text/javascript">' . "\n" . $content . "\n" . '</script>';
        } else {
            // For HTML, output directly
            echo $content;
        }

        return true;
    }

    /**
     * Check if current page is in block editor
     *
     * @return bool
     */
    private function is_block_editor()
    {
        if (function_exists('get_current_screen')) {
            $screen = get_current_screen();
            return $screen && $screen->is_block_editor();
        }

        return false;
    }

    /**
     * Get codes for shortcode output
     *
     * @param int $code_id Code ID.
     * @return string
     */
    public function get_code_for_shortcode($code_id)
    {
        $post = get_post($code_id);

        if (! $post || $post->post_type !== 'simple-custom-code') {
            return '';
        }

        $active = get_post_meta($code_id, '_scc_active', true);
        if (! $active) {
            return '';
        }

        $loading_method = get_post_meta($code_id, '_scc_loading_method', true);
        if ($loading_method !== 'manual') {
            return '';
        }

        $manual_type = get_post_meta($code_id, '_scc_manual_type', true);
        if ($manual_type !== 'shortcode') {
            return '';
        }


        $code_type = get_post_meta($code_id, '_scc_code_type', true);
        $file_manager = SCC_File_Manager::get_instance();
        $content = $file_manager->get_code_for_output($code_id);

        if (empty($content)) {
            return '';
        }

        $output = '';

        switch ($code_type) {
            case 'css':
                $output = '<style type="text/css">' . $content . '</style>';
                break;

            case 'js':
                $output = '<script type="text/javascript">' . $content . '</script>';
                break;

            case 'html':
                $output = $content;
                break;
        }

        return $output;
    }
}
