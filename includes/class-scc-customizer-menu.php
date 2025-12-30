<?php
class SCC_Customizer_Menu
{
    private static $instance = null;

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_bar_menu', [$this, 'add_admin_bar_item'], 100);
    }

    public function enqueue_assets()
    {
        if (!is_admin() && is_user_logged_in()) {
            wp_enqueue_script('scc-customizer-admin-js', SCC_PLUGIN_URL . 'assets/customizer/customizer-admin.js', ['jquery'], null, true);
            wp_localize_script('scc-customizer-admin-js', 'SCCCustomizer_Menu_Settings', [
                'rest_url' => esc_url_raw(rest_url('scc/v1/get-css-posts')),
                'nonce'    => wp_create_nonce('wp_rest'),
            ]);
            wp_enqueue_style('scc-customizer-admin-css', SCC_PLUGIN_URL . 'assets/customizer/customizer-admin.css');
        }
    }

    public function add_admin_bar_item($wp_admin_bar)
    {
        if (!is_admin() && is_user_logged_in()) {
            $current_settings = scc_get_settings();
            $access_role = isset($current_settings['access_role']) ? $current_settings['access_role'] : 'manage_options';
            if (current_user_can($access_role)) {
                $wp_admin_bar->add_node([
                    'id'    => 'scc_customizer',
                    'title' => 'CSS Customize',
                    'href'  => '#',
                    'meta'  => ['onclick' => 'SCCCustomizer.openOverlay(); return false;']
                ]);
            }
        }
    }

    public function register_rest_routes()
    {
        register_rest_route('scc/v1', '/get-css-posts', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_css_posts'],
            'permission_callback' => function () {
                return current_user_can('edit_posts');
            }
        ]);
    }

    public function get_css_posts($request)
    {
        $args = [
            'post_type'  => 'simple-custom-code',
            'meta_query' => [
                [
                    'key'   => '_scc_code_type',
                    'value' => 'css',
                ]
            ],
            'posts_per_page' => -1,
        ];

        // Easily extendable filter
        $args = apply_filters('scc_customizer_query_args', $args);

        $query = new WP_Query($args);
        $results = [];

        if ($query->have_posts()) {
            foreach ($query->posts as $post) {
                $results[] = [
                    'id'     => $post->ID,
                    'title'  => get_the_title($post),
                    'active' => get_post_meta($post->ID, '_scc_active', true),
                ];
            }
        }

        return rest_ensure_response($results);
    }
}

SCC_Customizer_Menu::get_instance();
