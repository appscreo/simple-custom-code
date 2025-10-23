<?php

/**
 * Settings page functionality
 *
 * @package CustomCodeManager
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * SCC_Settings class
 */
class SCC_Settings
{

    /**
     * Instance of this class
     *
     * @var SCC_Settings
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return SCC_Settings
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
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_scc_clear_cache', array($this, 'ajax_clear_cache'));
        add_action('admin_bar_menu', array($this, 'add_cache_clear_to_admin_bar'), 100);
        add_action('admin_notices', array($this, 'show_cache_notice'));
        add_action('current_screen', array($this, 'current_screen'));
    }

    function current_screen($current_screen)
    {

        if ($current_screen->post_type != 'simple-custom-code') {
            return false;
        }

        if ($current_screen->base == 'post') {
            add_action('admin_head', array($this, 'current_screen_post'));
        }

        if ($current_screen->base == 'edit') {
            add_action('admin_head', array($this, 'current_screen_edit'));
        }

        wp_deregister_script('autosave');
    }

    function current_screen_edit()
    {
?>
        <style>
            .page-title-action:not(.custom-page-title-action) {
                display: none !important;
            }
        </style>
        <script type="text/javascript">
            /* <![CDATA[ */
            jQuery(window).ready(function($) {
                var h1 = '<?php esc_html_e('Simple Custom Code', 'simple-custom-code'); ?> ';
                h1 += '<a href="post-new.php?post_type=simple-custom-code&language=css" class="page-title-action  custom-page-title-action"><?php esc_html_e('Add CSS Code', 'simple-custom-code'); ?></a>';
                h1 += '<a href="post-new.php?post_type=simple-custom-code&language=js" class="page-title-action  custom-page-title-action"><?php esc_html_e('Add JS Code', 'simple-custom-code'); ?></a>';
                h1 += '<a href="post-new.php?post_type=simple-custom-code&language=html" class="page-title-action  custom-page-title-action"><?php esc_html_e('Add HTML Code', 'simple-custom-code'); ?></a>';
                $("#wpbody-content h1").html(h1);
            });
        </script>
    <?php
    }

    function current_screen_post()
    {

        $strings = array(
            'Add CSS Code'   => esc_html__('Add CSS Code', 'simple-custom-code'),
            'Add JS Code'    => esc_html__('Add JS Code', 'simple-custom-code'),
            'Add HTML Code'  => esc_html__('Add HTML Code', 'simple-custom-code'),
            'Edit CSS Code'  => esc_html__('Edit CSS Code', 'simple-custom-code'),
            'Edit JS Code'   => esc_html__('Edit JS Code', 'simple-custom-code'),
            'Edit HTML Code' => esc_html__('Edit HTML Code', 'simple-custom-code'),
        );

        if (isset($_GET['post'])) {
            $action  = 'Edit';
            $post_id = esc_attr($_GET['post']);
        } else {
            $action  = 'Add';
            $post_id = false;
        }
        $language = $this->get_language($post_id);

        $title = $action . ' ' . strtoupper($language) . ' Code';
        $title = (isset($strings[$title])) ? $strings[$title] : $strings['Add CSS Code'];

        if ($action == 'Edit') {
            $title .= ' <a href="post-new.php?post_type=simple-custom-code&language=css" class="page-title-action custom-page-title-action">' . esc_html__('Add CSS Code', 'simple-custom-code') . '</a> ';
            $title .= '<a href="post-new.php?post_type=simple-custom-code&language=js" class="page-title-action custom-page-title-action">' . esc_html__('Add JS Code', 'simple-custom-code') . '</a>';
            $title .= '<a href="post-new.php?post_type=simple-custom-code&language=html" class="page-title-action custom-page-title-action">' . esc_html__('Add HTML Code', 'simple-custom-code') . '</a>';
        }

    ?>
        <style type="text/css">
            #post-body-content,
            .edit-form-section {
                position: static !important;
            }

            #ed_toolbar {
                display: none;
            }

            #postdivrich {
                display: none;
            }

            .page-title-action:not(.custom-page-title-action) {
                display: none !important;
            }
        </style>
        <script type="text/javascript">
            /* <![CDATA[ */
            jQuery(window).ready(function($) {
                $("#wpbody-content h1").html('<?php echo $title; ?>');
                $("#message.updated.notice").html('<p><?php esc_html_e('Code updated', 'simple-custom-code'); ?></p>');
            });
            /* ]]> */
        </script>
    <?php
    }

    function get_language($post_id = false)
    {

        $language = isset($_GET['language']) ? esc_attr(strtolower(wp_unslash($_GET['language']))) : 'css';
        if (! in_array($language, array('css', 'js', 'html'))) {
            $language = 'css';
        }

        return $language;
    }

    function add_new_buttons()
    {
        $current_screen = get_current_screen();

        if ((isset($current_screen->action) && $current_screen->action == 'add') || $current_screen->post_type != 'simple-custom-code') {
            return false;
        }
    ?>
        <div class="updated buttons">
            <a href="post-new.php?post_type=simple-custom-code&language=css" class="custom-btn custom-css-btn"><?php esc_html_e('Add CSS code', 'simple-custom-code'); ?></a>
            <a href="post-new.php?post_type=simple-custom-code&language=js" class="custom-btn custom-js-btn"><?php esc_html_e('Add JS code', 'simple-custom-code'); ?></a>
            <a href="post-new.php?post_type=simple-custom-code&language=html" class="custom-btn custom-js-btn"><?php esc_html_e('Add HTML code', 'simple-custom-code'); ?></a>
        </div>
    <?php
    }

    /**
     * Add settings page to admin menu
     */
    public function add_settings_page()
    {
        $current_settings = scc_get_settings();
        $access_role = isset($current_settings['access_role']) ? $current_settings['access_role'] : 'manage_options';

        $menu_slug    = 'edit.php?post_type=simple-custom-code';
        $submenu_slug = 'post-new.php?post_type=simple-custom-code';

        remove_submenu_page($menu_slug, $submenu_slug);

        $title = esc_html__('Add Custom CSS', 'simple-custom-code');
        add_submenu_page($menu_slug, $title, $title, $access_role, $submenu_slug . '&#038;language=css');

        $title = esc_html__('Add Custom JS', 'simple-custom-code');
        add_submenu_page($menu_slug, $title, $title, $access_role, $submenu_slug . '&#038;language=js');

        $title = esc_html__('Add Custom HTML', 'simple-custom-code');
        add_submenu_page($menu_slug, $title, $title, $access_role, $submenu_slug . '&#038;language=html');

        add_submenu_page(
            'edit.php?post_type=simple-custom-code',
            esc_html__('Simple Custom Code Settings', 'simple-custom-code'),
            esc_html__('Settings', 'simple-custom-code'),
            'manage_options',
            'scc-settings',
            array($this, 'settings_page_callback')
        );
    }

    /**
     * Register settings
     */
    public function register_settings()
    {
        register_setting(
            'scc_settings_group',
            'scc_settings',
            array($this, 'sanitize_settings')
        );

        // General settings section
        add_settings_section(
            'scc_general_settings',
            esc_html__('General Settings', 'simple-custom-code'),
            array($this, 'general_settings_callback'),
            'scc-settings'
        );

        add_settings_field(
            'editor_template',
            esc_html__('Editor Template', 'simple-custom-code'),
            array($this, 'editor_template_callback'),
            'scc-settings',
            'scc_general_settings'
        );

        add_settings_field(
            'access_role',
            esc_html__('Access Role', 'simple-custom-code'),
            array($this, 'access_role_callback'),
            'scc-settings',
            'scc_general_settings'
        );

        add_settings_field(
            'code_linting',
            esc_html__('Show code warnings and errors', 'simple-custom-code'),
            array($this, 'code_linting_callback'),
            'scc-settings',
            'scc_general_settings'
        );

        add_settings_field(
            'code_autocomplete',
            esc_html__('Autocomplete in the editor', 'simple-custom-code'),
            array($this, 'code_autocomplete_callback'),
            'scc-settings',
            'scc_general_settings'
        );

        add_settings_field(
            'code_disable_debug_comments',
            esc_html__('Code loading details', 'simple-custom-code'),
            array($this, 'code_disable_debug_comments_callback'),
            'scc-settings',
            'scc_general_settings'
        );


        add_settings_section(
            'scc_ai_settings',
            esc_html__('AI', 'simple-custom-code'),
            array($this, 'ai_settings_callback'),
            'scc-settings'
        );

        add_settings_field(
            'ai_key',
            esc_html__('Open AI Key', 'simple-custom-code'),
            array($this, 'ai_key_callback'),
            'scc-settings',
            'scc_ai_settings'
        );

        add_settings_field(
            'ai_model',
            esc_html__('Open AI Model', 'simple-custom-code'),
            array($this, 'ai_model_callback'),
            'scc-settings',
            'scc_ai_settings'
        );

        // Cache settings section
        add_settings_section(
            'scc_cache_settings',
            esc_html__('Cache Settings', 'simple-custom-code'),
            array($this, 'cache_settings_callback'),
            'scc-settings'
        );

        add_settings_field(
            'cache_mode',
            esc_html__('Cache Mode', 'simple-custom-code'),
            array($this, 'cache_mode_callback'),
            'scc-settings',
            'scc_cache_settings'
        );

        add_settings_field(
            'cache_type',
            esc_html__('Cache Type', 'simple-custom-code'),
            array($this, 'cache_type_callback'),
            'scc-settings',
            'scc_cache_settings'
        );

        add_settings_field(
            'cache_method',
            esc_html__('Cache Method', 'simple-custom-code'),
            array($this, 'cache_method_callback'),
            'scc-settings',
            'scc_cache_settings'
        );

        add_settings_field(
            'cache_optimized',
            esc_html__('Cache Optimized Loading', 'simple-custom-code'),
            array($this, 'cache_optimized_callback'),
            'scc-settings',
            'scc_cache_settings'
        );
    }

    /**
     * Settings page callback
     */
    public function settings_page_callback()
    {
        if (! scc_user_has_access()) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'simple-custom-code'));
        }
    ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <?php settings_errors(); ?>

            <form method="post" action="options.php">
                <?php
                settings_fields('scc_settings_group');
                do_settings_sections('scc-settings');
                submit_button();
                ?>
            </form>
        </div>
    <?php
    }

    public function ai_settings_callback()
    {
    ?>
        <div class="scc-ai-description">
            <p><?php echo esc_html__('Use the power of OpenAI to improve your productivity. Enter your API key, select an AI model, and start generating code using the power of AI.', 'simple-custom-code'); ?></p>
            <ol>
                <li>
                    <?php echo __('Create an account on <a href="https://platform.openai.com/account/api-keys" target="_blank">OpenAI website</a>.', 'simple-custom-code'); ?>
                </li>
                <li>
                    <?php echo esc_html__('Make a payment of at least $5 on the OpenAI platform.', 'simple-custom-code'); ?>
                </li>
                <li>
                    <?php echo esc_html__('Generate an OpenAI API key.', 'simple-custom-code'); ?>
                </li>
                <li>
                    <?php echo esc_html__('Paste it below and Save changes.', 'simple-custom-code'); ?>
                </li>
            </ol>
        </div>
<?php
    }

    /**
     * General settings section callback
     */
    public function general_settings_callback()
    {
        echo '<p>' . esc_html__('Configure general plugin settings.', 'simple-custom-code') . '</p>';
    }

    /**
     * Cache settings section callback
     */
    public function cache_settings_callback()
    {
        echo '<p>' . esc_html__('Configure cache settings to improve performance.', 'simple-custom-code') . '</p>';
    }

    /**
     * Editor template field callback
     */
    public function editor_template_callback()
    {
        $settings = scc_get_settings();
        $templates = array(
            '' => 'Default',
            '3024-day' => '3024-day',
            '3024-night' => '3024-night',
            'abbott' => 'abbott',
            'abcdef' => 'abcdef',
            'ambiance' => 'ambiance',
            'ayu-dark' => 'ayu-dark',
            'ayu-mirage' => 'ayu-mirage',
            'base16-dark' => 'base16-dark',
            'base16-light' => 'base16-light',
            'bespin' => 'bespin',
            'blackboard' => 'blackboard',
            'cobalt' => 'cobalt',
            'colorforth' => 'colorforth',
            'darcula' => 'darcula',
            'dracula' => 'dracula',
            'duotone-dark' => 'duotone-dark',
            'duotone-light' => 'duotone-light',
            'eclipse' => 'eclipse',
            'elegant' => 'elegant',
            'erlang-dark' => 'erlang-dark',
            'gruvbox-dark' => 'gruvbox-dark',
            'hopscotch' => 'hopscotch',
            'icecoder' => 'icecoder',
            'idea' => 'idea',
            'isotope' => 'isotope',
            'juejin' => 'juejin',
            'lesser-dark' => 'lesser-dark',
            'liquibyte' => 'liquibyte',
            'lucario' => 'lucario',
            'material' => 'material',
            'material-darker' => 'material-darker',
            'material-palenight' => 'material-palenight',
            'material-ocean' => 'material-ocean',
            'mbo' => 'mbo',
            'mdn-like' => 'mdn-like',
            'midnight' => 'midnight',
            'monokai' => 'monokai',
            'moxer' => 'moxer',
            'neat' => 'neat',
            'neo' => 'neo',
            'night' => 'night',
            'nord' => 'nord',
            'oceanic-next' => 'oceanic-next',
            'panda-syntax' => 'panda-syntax',
            'paraiso-dark' => 'paraiso-dark',
            'paraiso-light' => 'paraiso-light',
            'pastel-on-dark' => 'pastel-on-dark',
            'railscasts' => 'railscasts',
            'rubyblue' => 'rubyblue',
            'seti' => 'seti',
            'shadowfox' => 'shadowfox',
            'the-matrix' => 'the-matrix',
            'tomorrow-night-bright' => 'tomorrow-night-bright',
            'tomorrow-night-eighties' => 'tomorrow-night-eighties',
            'ttcn' => 'ttcn',
            'twilight' => 'twilight',
            'vibrant-ink' => 'vibrant-ink',
            'xq-dark' => 'xq-dark',
            'xq-light' => 'xq-light',
            'yeti' => 'yeti',
            'yonce' => 'yonce',
            'zenburn' => 'zenburn'
        );

        echo '<select id="editor_template" name="scc_settings[editor_template]">';
        foreach ($templates as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($settings['editor_template'], $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Select the editor template for code editing.', 'simple-custom-code') . '</p>';
    }

    /**
     * Access role field callback
     */
    public function access_role_callback()
    {
        $settings = scc_get_settings();
        $roles = array(
            'manage_options' => esc_html__('Administrator', 'simple-custom-code'),
            'delete_pages' => esc_html__('Editor', 'simple-custom-code'),
            'publish_posts' => esc_html__('Author', 'simple-custom-code'),
            'edit_posts' => esc_html__('Contributor', 'simple-custom-code'),
        );

        echo '<select id="access_role" name="scc_settings[access_role]">';
        foreach ($roles as $role => $name) {
            echo '<option value="' . esc_attr($role) . '" ' . selected($settings['access_role'], $role, false) . '>' . esc_html($name) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Select the minimum role required to access the Simple Custom Code for adding and editing custom code. Access to settings will always require administrative privileges.', 'simple-custom-code') . '</p>';
    }

    public function ai_key_callback()
    {
        $settings = scc_get_settings();
        echo '<input type="text" name="scc_settings[ai_key]" value="' . esc_attr($settings['ai_key']) .  '" style="min-width: 350px;" />';
    }

    public function ai_model_callback()
    {
        $settings = scc_get_settings();

        $chat_completion_models = array(
            'gpt-5'                => 'GPT-5',
            'gpt-4.1'              => 'GPT-4.1',
            'gpt-4.1-mini'         => 'GPT-4.1 Mini',
            'gpt-4.1-nano'         => 'GPT-4.1 Nano',
            'gpt-4o'               => 'GPT-4o (Omni)',
            'gpt-4o-mini'          => 'GPT-4o Mini',
            'gpt-3.5-turbo'        => 'GPT-3.5 Turbo',
            'gpt-3.5-turbo-instruct' => 'GPT-3.5 Turbo Instruct',
        );


        echo '<select id="ai_model" name="scc_settings[ai_model]">';
        foreach ($chat_completion_models as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($settings['ai_model'], $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
    }

    public function code_disable_debug_comments_callback()
    {
        $settings = scc_get_settings();

        echo '<div class="scc-pro-only">';
        echo '<div class="scc-pro-content">';


        echo '<label>';
        echo '<input type="checkbox" id="code_disable_debug_comments" name="scc_settings[code_disable_debug_comments]" value="1" ' . checked($settings['code_disable_debug_comments'], true, false) . ' />';
        echo ' ' . esc_html__('Disable loaded codes comments in the source of the page', 'simple-custom-code');
        echo '</label>';

        echo '</div>';
        echo '<div class="scc-pro-message">';
        echo '<a href="' . esc_url(scc_get_pro_url()) . '" target="_blank">' . esc_html__('Available in Pro', 'simple-custom-code') . '</a>';
        echo '</div>';
        echo '</div>';
    }

    public function code_autocomplete_callback()
    {
        $settings = scc_get_settings();

        echo '<label>';
        echo '<input type="checkbox" id="code_autocomplete" name="scc_settings[code_autocomplete]" value="1" ' . checked($settings['code_autocomplete'], true, false) . ' />';
        echo ' ' . esc_html__('Enable code autocomplete in the editor', 'simple-custom-code');
        echo '</label>';
    }

    public function code_linting_callback()
    {
        $settings = scc_get_settings();

        echo '<label>';
        echo '<input type="checkbox" id="code_linting" name="scc_settings[code_linting]" value="1" ' . checked($settings['code_linting'], true, false) . ' />';
        echo ' ' . esc_html__('Enable code warnings in the editor', 'simple-custom-code');
        echo '</label>';
    }

    /**
     * Cache mode field callback
     */
    public function cache_mode_callback()
    {
        $settings = scc_get_settings();

        echo '<div class="scc-pro-only">';
        echo '<div class="scc-pro-content">';


        echo '<label>';
        echo '<input type="checkbox" id="cache_mode" name="scc_settings[cache_mode]" value="1" ' . checked($settings['cache_mode'], true, false) . ' />';
        echo ' ' . esc_html__('Enable cache mode', 'simple-custom-code');
        echo '</label>';
        echo '<p class="description">' . esc_html__('When enabled, codes will be cached for better performance.', 'simple-custom-code') . '</p>';

        echo '</div>';
        echo '<div class="scc-pro-message">';
        echo '<a href="' . esc_html(scc_get_pro_url()) . '" target="_blank">' . esc_html__('Available in Pro', 'simple-custom-code') . '</a>';
        echo '</div>';
        echo '</div>';
    }

    public function cache_optimized_callback()
    {
        $settings = scc_get_settings();
        $cache_types = array(
            'no' => esc_html__('No', 'simple-custom-code'),
            'css_js' => esc_html__('CSS and JavaScript', 'simple-custom-code'),
            'css' => esc_html__('CSS only', 'simple-custom-code'),
            'js' => esc_html__('JavaScript only', 'simple-custom-code')
        );

        echo '<select id="cache_optimized" name="scc_settings[cache_optimized]">';
        foreach ($cache_types as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($settings['cache_optimized'], $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
    }

    /**
     * Cache type field callback
     */
    public function cache_type_callback()
    {
        $settings = scc_get_settings();
        $cache_types = array(
            'css_js' => esc_html__('CSS and JavaScript', 'simple-custom-code'),
            'css' => esc_html__('CSS only', 'simple-custom-code'),
            'js' => esc_html__('JavaScript only', 'simple-custom-code')
        );

        echo '<select id="cache_type" name="scc_settings[cache_type]">';
        foreach ($cache_types as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($settings['cache_type'], $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Select which types of code should be cached.', 'simple-custom-code') . '</p>';
    }

    /**
     * Cache method field callback
     */
    public function cache_method_callback()
    {
        $settings = scc_get_settings();
        $cache_methods = array(
            'file' => esc_html__('File', 'simple-custom-code'),
            'inline' => esc_html__('Inline', 'simple-custom-code')
        );

        echo '<select id="cache_method" name="scc_settings[cache_method]">';
        foreach ($cache_methods as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($settings['cache_method'], $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Select how cached code should be loaded.', 'simple-custom-code') . '</p>';
    }

    /**
     * Sanitize settings
     */
    public function sanitize_settings($input)
    {
        $sanitized = array();

        $sanitized['editor_template'] = sanitize_text_field($input['editor_template']);
        $sanitized['access_role'] = sanitize_text_field($input['access_role']);

        $sanitized['code_linting'] = isset($input['code_linting']) ? true : false;
        $sanitized['code_autocomplete'] = isset($input['code_autocomplete']) ? true : false;


        $sanitized['ai_key'] = sanitize_text_field($input['ai_key']);
        $sanitized['ai_model'] = sanitize_text_field($input['ai_model']);

        // Clear cache when settings change
        if (
            $sanitized['cache_mode'] !== scc_get_settings()['cache_mode'] ||
            $sanitized['cache_type'] !== scc_get_settings()['cache_type'] ||
            $sanitized['cache_method'] !== scc_get_settings()['cache_method'] ||
            $sanitized['cache_optimized'] !== scc_get_settings()['cache_optimized']
        ) {
            $cache = SCC_Cache::get_instance();
            $cache->clear_all_cache();
        }

        return $sanitized;
    }

    /**
     * Show cache notice
     */
    public function show_cache_notice()
    {
        $screen = get_current_screen();

        if (! $screen || ! in_array($screen->id, array('edit-simple-custom-code', 'simple-custom-code_page_scc-settings'))) {
            return;
        }

        $settings = scc_get_settings();

        if ($settings['cache_mode']) {
            $cache_url = wp_nonce_url(
                admin_url('admin-ajax.php?action=scc_clear_cache'),
                'scc_clear_cache'
            );

            echo '<div class="notice notice-info">';
            echo '<p>';
            echo '<strong>' . esc_html__('Cache Mode is Active', 'simple-custom-code') . '</strong> - ';
            echo esc_html__('Your custom codes are being cached for better performance.', 'simple-custom-code');
            echo ' <a href="#" id="scc-clear-cache-link" class="button button-small">' . esc_html__('Clear Cache', 'simple-custom-code') . '</a>';
            echo '</p>';
            echo '</div>';
        }
    }

    /**
     * Add cache clear button to admin bar
     */
    public function add_cache_clear_to_admin_bar($wp_admin_bar)
    {
        if (! scc_user_has_access()) {
            return;
        }

        $settings = scc_get_settings();

        if (! $settings['cache_mode']) {
            return;
        }

        $wp_admin_bar->add_node(array(
            'id' => 'scc-clear-cache',
            'title' => esc_html__('Clear CCM Cache', 'simple-custom-code'),
            'href' => '#',
            'meta' => array(
                'class' => 'scc-clear-cache-button'
            )
        ));
    }

    /**
     * AJAX handler to clear cache
     */
    public function ajax_clear_cache()
    {
        if (! wp_verify_nonce($_GET['_wpnonce'] ?? $_POST['_wpnonce'] ?? '', 'scc_clear_cache')) {
            wp_die(esc_html__('Invalid nonce', 'simple-custom-code'));
        }

        if (! scc_user_has_access()) {
            wp_die(esc_html__('You do not have permission to clear cache.', 'simple-custom-code'));
        }

        $cache = SCC_Cache::get_instance();
        $result = $cache->clear_all_cache();

        if (defined('DOING_AJAX') && DOING_AJAX) {
            wp_send_json_success(array(
                'message' => esc_html__('Cache cleared successfully!', 'simple-custom-code')
            ));
        } else {
            wp_redirect(wp_get_referer());
            exit;
        }
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook)
    {
        $screen = get_current_screen();

        if (! $screen || ! in_array($screen->id, array('edit-simple-custom-code', 'simple-custom-code_page_scc-settings'))) {
            return;
        }

        wp_enqueue_script(
            'scc-admin-settings',
            SCC_PLUGIN_URL . 'assets/js/admin-settings.js',
            array('jquery'),
            SCC_VERSION,
            true
        );

        wp_localize_script(
            'scc-admin-settings',
            'ccmSettings',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('scc_clear_cache'),
                'strings' => array(
                    'clearing_cache' => esc_html__('Clearing cache...', 'simple-custom-code'),
                    'cache_cleared' => esc_html__('Cache cleared successfully!', 'simple-custom-code'),
                    'error' => esc_html__('An error occurred. Please try again.', 'simple-custom-code')
                )
            )
        );

        wp_enqueue_style(
            'scc-admin-settings',
            SCC_PLUGIN_URL . 'assets/css/admin-settings.css',
            array(),
            SCC_VERSION
        );
    }
}
