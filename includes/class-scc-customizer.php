<?php
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SCC_Customizer
{
    private static $instance = null;

    /**
     * Singleton access
     */
    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        add_action('rest_api_init', [$this, 'register_rest_endpoint']);
        add_action('template_redirect', [$this, 'maybe_initialize']);
    }

    /**
     * Check URL parameters and initialize if valid
     */
    public function maybe_initialize()
    {
        if (
            isset($_GET['scc_customizer']) && $_GET['scc_customizer'] === 'true' &&
            isset($_GET['scc']) && is_numeric($_GET['scc'])
        ) {
            $post_id = intval($_GET['scc']);
            $post    = get_post($post_id);

            if ($post && $post->post_type === 'simple-custom-code') {
                add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
            }
        }
    }

    /**
     * Enqueue CSS and JS assets
     */
    public function enqueue_assets()
    {
        $plugin_url = SCC_PLUGIN_URL;

        wp_enqueue_style(
            'scc-customizer-style',
            $plugin_url . 'assets/customizer/customizer.css',
            [],
            '1.0'
        );

        wp_enqueue_script(
            'scc-customizer-script',
            $plugin_url . 'assets/customizer/customizer.js',
            ['jquery'],
            '1.0',
            true
        );

        wp_localize_script('scc-customizer-script', 'sccCustomizerData', [
            'nonce'   => wp_create_nonce('wp_rest'),
            'rest_url' => esc_url_raw(rest_url('sccparser/v1/save')),
            'post_id' => intval($_GET['scc']),
        ]);
    }

    /**
     * Register REST endpoint
     */
    public function register_rest_endpoint()
    {
        register_rest_route('sccparser/v1', '/save', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handle_save'],
            'permission_callback' => function () {
                return current_user_can('edit_posts');
            },
        ]);
    }

    /**
     * Verify nonce for REST request
     */
    public function verify_request($request)
    {
        $nonce = $request->get_header('X-WP-Nonce');
        return wp_verify_nonce($nonce, 'scc_customizer_nonce');
    }

    /**
     * Handle saving CSS content
     */
    public function handle_save($request)
    {
        $post_id = intval($request->get_param('post_id'));
        $content = sanitize_textarea_field($request->get_param('content'));

        if (get_post_type($post_id) !== 'simple-custom-code') {
            return new WP_Error('invalid_post', 'Invalid post type.', ['status' => 400]);
        }

        //update_post_meta( $post_id, '_scc_custom_content', $content );
        if (!empty($content)) {
            $file_manager = SCC_File_Manager::get_instance();
            $code_content = $file_manager->get_code_content($post_id);
            $code_content .= "\n" . wp_unslash($content);
            $file_manager->save_code_content($post_id, $code_content);
        }

        return rest_ensure_response([
            'success' => true,
            'message' => 'CSS content saved successfully.',
        ]);
    }
}

SCC_Customizer::get_instance();
