<?php

/**
 * Meta boxes functionality
 *
 * @package CustomCodeManager
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * SCC_Meta_Boxes class
 */
class SCC_Meta_Boxes
{

    /**
     * Instance of this class
     *
     * @var SCC_Meta_Boxes
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return SCC_Meta_Boxes
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
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('edit_form_after_title', array($this, 'add_permalink_box'));
        add_action('wp_ajax_scc_generate_ai_code', array($this, 'handle_generate_ai_code'));
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes()
    {
        add_meta_box(
            'scc-code-content',
            __('Code Content', 'simple-custom-code'),
            array($this, 'code_content_callback'),
            'simple-custom-code',
            'normal',
            'high'
        );

        add_meta_box(
            'scc-code-settings',
            __('Code Settings', 'simple-custom-code'),
            array($this, 'code_settings_callback'),
            'simple-custom-code',
            'side',
            'core'
        );

        add_meta_box(
            'scc-loading-conditions',
            __('Loading Conditions', 'simple-custom-code'),
            array($this, 'loading_conditions_callback'),
            'simple-custom-code',
            'normal',
            'default'
        );
    }

    public function handle_generate_ai_code()
    {
        check_ajax_referer('scc_ai_generate_nonce', 'nonce');

        if (! current_user_can('edit_posts')) {
            wp_send_json(['code' => 9, 'generated' => __('Permission denied.', 'simple-custom-code')]);
        }

        $allowedTypes = ["css", "js", "html"];

        $response = array('code' => 0, 'generated' => '');
        $prompt = sanitize_text_field(wp_unslash($_POST['prompt']) ?? '');
        $advanced = sanitize_text_field(wp_unslash($_POST['advanced']) ?? '');

        $mode = sanitize_text_field(wp_unslash($_POST['mode']) ?? '');
        if (function_exists('scc_generate_custom_code')) {
            if (in_array($mode, $allowedTypes, true)) {
                $code_response = scc_generate_custom_code($mode, $prompt, $advanced);
                if ($code_response['status'] == 'success') {
                    $response['code'] = 1;
                    $response['generated'] = $code_response['code'];
                } else {
                    $response['code'] = 2;
                    $response['generated'] = $code_response['code'];
                }
            } else {
                $response['code'] = 8;
                $response['generated'] = __('Type not allowed', 'simple-custom-code');
            }
        } else {
            $response['code'] = 7;
            $response['generated'] = __('AI engine is missing', 'simple-custom-code');
        }

        wp_send_json($response);
    }

    /**
     * Code content meta box callback
     */
    public function code_content_callback($post)
    {
        wp_nonce_field('scc_save_meta_boxes', 'scc_meta_nonce');

        $code_type = get_post_meta($post->ID, '_scc_code_type', true);
        if (empty($code_type) && isset($_REQUEST['language'])) {
            $code_type = esc_attr(wp_unslash($_REQUEST['language']));
        }
        $code_type = $code_type ? $code_type : 'css';

        // Get code content from file
        $file_manager = SCC_File_Manager::get_instance();
        $code_content = $file_manager->get_code_content($post->ID);

        $current_options = scc_get_settings();

?>
        <div id="scc-code-editor-wrapper" data-lint="<?php echo esc_attr($current_options['code_linting']); ?>" data-autocomplete="<?php echo esc_attr($current_options['code_autocomplete']); ?>" data-theme="<?php echo esc_attr($current_options['editor_template']); ?>">
            <div class="scc-code-editor-topbar">
                <div class="scc-primary-actions">
                    <button id="formatCodeBtn" class="scc-button button button-secondary">
                        <svg width="800px" height="800px" viewBox="0 0 96 96" xmlns="http://www.w3.org/2000/svg">
                            <g>
                                <path d="M24.8452,25.3957a6.0129,6.0129,0,0,0-8.4487.7617L1.3974,44.1563a5.9844,5.9844,0,0,0,0,7.687L16.3965,69.8422a5.9983,5.9983,0,1,0,9.21-7.687L13.8068,48l11.8-14.1554A6,6,0,0,0,24.8452,25.3957Z" />
                                <path d="M55.1714,12.1192A6.0558,6.0558,0,0,0,48.1172,16.83L36.1179,76.8262A5.9847,5.9847,0,0,0,40.8286,83.88a5.7059,5.7059,0,0,0,1.1835.1172A5.9949,5.9949,0,0,0,47.8828,79.17L59.8821,19.1735A5.9848,5.9848,0,0,0,55.1714,12.1192Z" />
                                <path d="M94.6026,44.1563,79.6035,26.1574a5.9983,5.9983,0,1,0-9.21,7.687L82.1932,48l-11.8,14.1554a5.9983,5.9983,0,1,0,9.21,7.687L94.6026,51.8433A5.9844,5.9844,0,0,0,94.6026,44.1563Z" />
                            </g>
                        </svg>
                        <label>Format Code</label>
                    </button>
                    <button id="scc-ai-generate-button" class="scc-button button button-secondary"><svg width="800px" height="800px" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" fill="#000000" class="bi bi-stars">
                            <path d="M7.657 6.247c.11-.33.576-.33.686 0l.645 1.937a2.89 2.89 0 0 0 1.829 1.828l1.936.645c.33.11.33.576 0 .686l-1.937.645a2.89 2.89 0 0 0-1.828 1.829l-.645 1.936a.361.361 0 0 1-.686 0l-.645-1.937a2.89 2.89 0 0 0-1.828-1.828l-1.937-.645a.361.361 0 0 1 0-.686l1.937-.645a2.89 2.89 0 0 0 1.828-1.828l.645-1.937zM3.794 1.148a.217.217 0 0 1 .412 0l.387 1.162c.173.518.579.924 1.097 1.097l1.162.387a.217.217 0 0 1 0 .412l-1.162.387A1.734 1.734 0 0 0 4.593 5.69l-.387 1.162a.217.217 0 0 1-.412 0L3.407 5.69A1.734 1.734 0 0 0 2.31 4.593l-1.162-.387a.217.217 0 0 1 0-.412l1.162-.387A1.734 1.734 0 0 0 3.407 2.31l.387-1.162zM10.863.099a.145.145 0 0 1 .274 0l.258.774c.115.346.386.617.732.732l.774.258a.145.145 0 0 1 0 .274l-.774.258a1.156 1.156 0 0 0-.732.732l-.258.774a.145.145 0 0 1-.274 0l-.258-.774a1.156 1.156 0 0 0-.732-.732L9.1 2.137a.145.145 0 0 1 0-.274l.774-.258c.346-.115.617-.386.732-.732L10.863.1z" />
                        </svg> <label>Generate with AI</label></button>

                    <div id="scc-ai-overlay-container" style="display:none;"></div>
                    <div id="scc-ai-result-overlay" style="display:none;"></div>
                </div>

                <div class="scc-editor-right-controls">
                    <div class="scc-code-type-buttons">
                        <button type="button" id="scc-css-btn" class="scc-code-type-btn <?php echo $code_type === 'css' ? 'active' : ''; ?>" data-code-type="css" title="<?php esc_html_e('CSS', 'simple-custom-code'); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" role="img" aria-labelledby="cssTitle" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                <title id="cssTitle">CSS</title>
                                <!-- outer rounded square -->
                                <rect x="3" y="3" width="18" height="18" rx="3"></rect>
                                <!-- three stacked lines representing stylesheet layers -->
                                <line x1="7" y1="8.5" x2="17" y2="8.5"></line>
                                <line x1="7" y1="12" x2="17" y2="12"></line>
                                <line x1="7" y1="15.5" x2="17" y2="15.5"></line>
                            </svg>

                            CSS
                        </button>
                        <button type="button" id="scc-js-btn" class="scc-code-type-btn <?php echo $code_type === 'js' ? 'active' : ''; ?>" data-code-type="js" title="<?php esc_html_e('JavaScript', 'simple-custom-code'); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" role="img" aria-labelledby="jsTitle" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                <title id="jsTitle">JavaScript</title>
                                <!-- left curly -->
                                <path d="M10 5 C8.8 5 8 6.5 8 8.5 C8 10.5 9.2 11.5 9.2 12 C9.2 12.5 8 13.5 8 15.5 C8 17.5 8.8 19 10 19"></path>
                                <!-- right curly -->
                                <path d="M14 5 C15.2 5 16 6.5 16 8.5 C16 10.5 14.8 11.5 14.8 12 C14.8 12.5 16 13.5 16 15.5 C16 17.5 15.2 19 14 19"></path>
                            </svg>

                            JS
                        </button>
                        <button type="button" id="scc-html-btn" class="scc-code-type-btn <?php echo $code_type === 'html' ? 'active' : ''; ?>" data-code-type="html" title="<?php esc_html_e('HTML', 'simple-custom-code'); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" role="img" aria-labelledby="htmlTitle" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                <title id="htmlTitle">HTML</title>
                                <!-- left angle -->
                                <polyline points="9 7 5 12 9 17"></polyline>
                                <!-- right angle -->
                                <polyline points="15 7 19 12 15 17"></polyline>
                            </svg>
                            HTML
                        </button>
                    </div>
                </div>
            </div>

            <textarea id="scc-code-editor" name="scc_code_content" rows="20" style="width: 100%; font-family: monospace;"><?php echo esc_textarea($code_content); ?></textarea>
        </div>
    <?php
    }

    /**
     * Code settings meta box callback
     */
    public function code_settings_callback($post)
    {
        $values = get_post_meta($post->ID);

        // Default values
        $defaults = array(
            '_scc_code_type' => 'css',
            '_scc_loading_method' => 'automatic',
            '_scc_manual_type' => 'shortcode',
            '_scc_action_filter_name' => '',
            '_scc_loading_locations' => array('frontend'),
            '_scc_priority' => 10,
            '_scc_code_position' => 'head',
            '_scc_linking_type' => 'internal',
            '_scc_minify_enabled' => false,
            '_scc_css_optimize_enabled' => 'no',
            '_scc_js_optimize_enabled' => 'no',
            '_scc_css_preprocessor' => 'none',
            '_scc_active' => true
        );

        foreach ($defaults as $key => $default) {
            if (isset($values[$key][0])) {
                $values[$key] = maybe_unserialize($values[$key][0]);
            } else {
                $values[$key] = $default;

                if ($key == '_scc_code_type' && isset($_REQUEST['language'])) {
                    $values[$key] = esc_attr(wp_unslash($_REQUEST['language']));
                }
            }
        }
    ?>

        <div class="scc-form-table">
            <div class="row">
                <div><label for="scc_active"><?php esc_html_e('Status', 'simple-custom-code'); ?></label></div>
                <div>
                    <select id="scc_active" name="scc_active">
                        <option value="1" <?php selected($values['_scc_active'], true); ?>><?php esc_html_e('Active', 'simple-custom-code'); ?></option>
                        <option value="0" <?php selected($values['_scc_active'], false); ?>><?php esc_html_e('Inactive', 'simple-custom-code'); ?></option>
                    </select>
                </div>
            </div>

            <div class="row" style="display: none;">
                <div><label for="scc_code_type"><?php esc_html_e('Code Type', 'simple-custom-code'); ?></label></div>
                <div>
                    <select id="scc_code_type" name="scc_code_type">
                        <?php foreach (scc_get_code_types() as $key => $label) : ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($values['_scc_code_type'], $key); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row" id="scc_linking_type_row" style="<?php echo $values['_scc_code_type'] !== 'html' ? '' : 'display: none;'; ?>">
                <div><label for="scc_linking_type"><?php esc_html_e('Linking Type', 'simple-custom-code'); ?></label></div>
                <div>
                    <select id="scc_linking_type" name="scc_linking_type">
                        <?php foreach (scc_get_linking_types() as $key => $label) : ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($values['_scc_linking_type'], $key); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="scc-pro-only">
                <div class="scc-pro-content">
                    <div class="row">
                        <div><label for="scc_loading_method"><?php esc_html_e('Loading Method', 'simple-custom-code'); ?></label></div>
                        <div>
                            <select id="scc_loading_method" name="scc_loading_method">
                                <?php foreach (scc_get_loading_methods() as $key => $label) : ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($values['_scc_loading_method'], $key); ?>><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="scc-pro-message">
                    <a href="<?php echo esc_url(scc_get_pro_url()); ?>" target="_blank"><?php esc_html_e('Available in Pro', 'simple-custom-code'); ?> </a>
                </div>
            </div>

            <div class="row" id="scc_code_position_row" style="<?php echo $values['_scc_loading_method'] === 'automatic' ? '' : 'display: none;'; ?>">
                <div><label for="scc_code_position"><?php esc_html_e('Code Position', 'simple-custom-code'); ?></label></div>
                <div>
                    <select id="scc_code_position" name="scc_code_position">
                        <?php foreach (scc_get_code_positions() as $key => $label) : ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($values['_scc_code_position'], $key); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div id="scc_manual_options" class="row" style="<?php echo $values['_scc_loading_method'] === 'manual' ? '' : 'display: none;'; ?>">
                <div><label for="scc_manual_type"><?php esc_html_e('Manual Type', 'simple-custom-code'); ?></label></div>
                <div>
                    <select id="scc_manual_type" name="scc_manual_type">
                        <?php foreach (scc_get_manual_types() as $key => $label) : ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($values['_scc_manual_type'], $key); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>


            <div id="scc_loading_locations_row" class="row" style="<?php echo $values['_scc_loading_method'] === 'automatic' ? '' : 'display: none;'; ?>">
                <div><label><?php esc_html_e('Loading Locations', 'simple-custom-code'); ?></label></div>
                <div>
                    <?php foreach (scc_get_loading_locations() as $key => $label) : ?>
                        <label style="display: block; margin-bottom: 5px;">
                            <input type="checkbox" name="scc_loading_locations[]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, (array) $values['_scc_loading_locations'])); ?> />
                            <?php echo esc_html($label); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="row">
                <div><label for="scc_priority"><?php esc_html_e('Priority', 'simple-custom-code'); ?></label></div>
                <div>
                    <select id="scc_priority" name="scc_priority">
                        <?php foreach (scc_get_priority_options() as $key => $label) : ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($values['_scc_priority'], $key); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="scc-pro-only">
                <div class="scc-pro-content">

                    <div class="row">
                        <div><label for="scc_minify_enabled"><?php esc_html_e('Minify Code', 'simple-custom-code'); ?></label></div>
                        <div>
                            <label>
                                <input type="checkbox" id="scc_minify_enabled" name="scc_minify_enabled" value="1" <?php checked($values['_scc_minify_enabled']); ?> />
                                <?php esc_html_e('Enable code minification', 'simple-custom-code'); ?>
                            </label>
                        </div>
                    </div>

                    <div id="scc_css_optimize_enabled_row" class="row" style="<?php echo $values['_scc_code_type'] === 'css' && $values['_scc_linking_type'] == 'external' ? '' : 'display: none;'; ?>">
                        <div><label for="scc_css_optimize_enabled"><?php esc_html_e('Optimized Loading', 'simple-custom-code'); ?></label></div>
                        <div>
                            <select id="scc_css_optimize_enabled" name="scc_css_optimize_enabled">
                                <option value="no" <?php selected($values['_scc_css_optimize_enabled'], 'no'); ?>><?php echo esc_html('No', 'simple-custom-code'); ?></option>
                                <option value="yes" <?php selected($values['_scc_css_optimize_enabled'], 'yes'); ?>><?php echo esc_html('Yes', 'simple-custom-code'); ?></option>
                            </select>
                        </div>
                    </div>

                    <div id="scc_js_optimize_enabled_row" class="row" style="<?php echo $values['_scc_code_type'] === 'js' && $values['_scc_linking_type'] == 'external' ? '' : 'display: none;'; ?>">
                        <div><label for="scc_js_optimize_enabled"><?php esc_html_e('Optimized Loading', 'simple-custom-code'); ?></label></div>
                        <div>
                            <select id="scc_js_optimize_enabled" name="scc_js_optimize_enabled">
                                <option value="no" <?php selected($values['_scc_js_optimize_enabled'], 'no'); ?>><?php echo esc_html('No', 'simple-custom-code'); ?></option>
                                <option value="yes" <?php selected($values['_scc_js_optimize_enabled'], 'yes'); ?>><?php echo esc_html('Yes', 'simple-custom-code'); ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="row" id="scc_css_preprocessor_row" style="<?php echo $values['_scc_code_type'] === 'css' ? '' : 'display: none;'; ?>">
                        <div><label for="scc_css_preprocessor"><?php esc_html_e('CSS Preprocessor', 'simple-custom-code'); ?></label></div>
                        <div>
                            <select id="scc_css_preprocessor" name="scc_css_preprocessor">
                                <?php foreach (scc_get_css_preprocessors() as $key => $label) : ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($values['_scc_css_preprocessor'], $key); ?>><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="scc-pro-message">
                    <a href="<?php echo esc_url(scc_get_pro_url()); ?>" target="_blank"><?php esc_html_e('Available in Pro', 'simple-custom-code'); ?> </a>
                </div>
            </div>
        </div>
    <?php
    }

    /**
     * Loading conditions meta box callback
     */
    public function loading_conditions_callback($post)
    {
        $conditions = get_post_meta($post->ID, '_scc_loading_conditions', true);
        $conditions = is_array($conditions) ? $conditions : array();

        $loading_method = get_post_meta($post->ID, '_scc_loading_method', true);
        $loading_method = $loading_method ? $loading_method : 'automatic';

        $loading_manual_type = get_post_meta($post->ID, '_scc_manual_type', true);
        $loading_action_filter_name = get_post_meta($post->ID, '_scc_action_filter_name', true);
    ?>

        <div class="scc-pro-only">
            <div class="scc-pro-content">

                <div id="scc_manual_shortcode" class="scc-add-condition-section" style="<?php echo ($loading_method === 'manual' && in_array($loading_manual_type, array('shortcode'))) ? '' : 'display: none;'; ?>">
                    <div><label><?php esc_html_e('Shortcode', 'simple-custom-code'); ?></label></div>
                    <div class="scc-manual-shortcode-block">
                        <code id="scc-shortcode-value">[simple_custom_code id="<?php echo esc_attr($post->ID); ?>"]</code>
                        <button type="button" id="scc-copy-shortcode" class="button button-small">Copy</button>
                    </div>
                </div>

                <div id="scc_action_filter_name_row" class="scc-add-condition-section" style="<?php echo ($loading_method === 'manual' && in_array($loading_manual_type, array('action', 'filter'))) ? '' : 'display: none;'; ?>">
                    <div><label for="scc_action_filter_name"><?php esc_html_e('Hook Name', 'simple-custom-code'); ?></label></div>
                    <div style="flex: 1;">
                        <input type="text" id="scc_action_filter_name" name="scc_action_filter_name" value="<?php echo esc_attr($loading_action_filter_name); ?>" style="width: 100%;" />
                    </div>
                </div>

                <div id="scc_loading_conditions_wrapper" style="<?php echo $loading_method === 'manual' ? 'display: none;' : ''; ?>">
                    <p><?php esc_html_e('Set conditions to control where this code will be loaded on your website.', 'simple-custom-code'); ?></p>

                    <div id="scc_conditions_list">
                        <?php if (! empty($conditions)) : ?>
                            <?php foreach ($conditions as $index => $condition) : ?>
                                <div class="scc-condition-row" data-index="<?php echo esc_attr($index); ?>">
                                    <select name="scc_conditions[<?php echo esc_attr($index); ?>][operator]">
                                        <?php foreach (scc_get_condition_operators() as $key => $label) : ?>
                                            <option value="<?php echo esc_attr($key); ?>" <?php selected($condition['operator'], $key); ?>><?php echo esc_html($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" name="scc_conditions[<?php echo esc_attr($index); ?>][url]" value="<?php echo esc_attr($condition['url']); ?>" placeholder="<?php esc_html_e('URL or pattern', 'simple-custom-code'); ?>" class="regular-text" />
                                    <button type="button" class="button scc-remove-condition"><?php esc_html_e('Remove', 'simple-custom-code'); ?></button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="scc-add-condition-section">
                        <select id="scc_new_condition_operator">
                            <?php foreach (scc_get_condition_operators() as $key => $label) : ?>
                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" id="scc_new_condition_url" placeholder="<?php esc_html_e('URL or pattern', 'simple-custom-code'); ?>" class="regular-text" />
                        <button type="button" id="scc_add_condition" class="button button-secondary"><?php esc_html_e('Add Condition', 'simple-custom-code'); ?></button>
                    </div>

                    <p class="description">
                        <?php esc_html_e('Examples:', 'simple-custom-code'); ?><br>
                        <?php esc_html_e('• Contains: "shop" - loads on pages containing "shop" in URL', 'simple-custom-code'); ?><br>
                        <?php esc_html_e('• Equals: "/about" - loads only on the about page', 'simple-custom-code'); ?><br>
                        <?php esc_html_e('• Starts with: "/product/" - loads on all product pages', 'simple-custom-code'); ?>
                    </p>
                </div>

            </div>
            <div class="scc-pro-message">
                <a href="<?php echo esc_url(scc_get_pro_url()); ?>" target="_blank"><?php esc_html_e('Available in Pro', 'simple-custom-code'); ?> </a>
            </div>
        </div>
    <?php
    }

    /**
     * Add permalink box after title
     */
    public function add_permalink_box($post)
    {
        if ('simple-custom-code' !== $post->post_type) {
            return;
        }

        $permalink = get_post_meta($post->ID, '_scc_custom_permalink', true);
        $permalink = $permalink ? $permalink : $post->ID;

        $upload_url = scc_get_upload_url();
        $code_type = get_post_meta($post->ID, '_scc_code_type', true);

        if (empty($code_type) && isset($_REQUEST['language'])) {
            $code_type = esc_attr(wp_unslash($_REQUEST['language']));
        }

        $code_type = $code_type ? $code_type : 'css';

        $file_extension = '';
        switch ($code_type) {
            case 'css':
                $file_extension = '.css';
                break;
            case 'js':
                $file_extension = '.js';
                break;
            case 'html':
                $file_extension = '.html';
                break;
        }
    ?>

        <div id="scc-permalink-box" class="postbox">
            <div class="inside">
                <div class="scc-permalink-location">
                    <strong><?php esc_html_e('File Location:', 'simple-custom-code'); ?></strong>
                    <code><?php echo esc_url($upload_url); ?><span id="scc-permalink-display"><?php echo esc_html($permalink); ?></span><span id="scc-file-extension"><?php echo esc_html($file_extension); ?></span></code>
                    <button type="button" id="scc-permalink-edit-button" class="button button-small"><?php esc_html_e('Edit', 'simple-custom-code'); ?></button>
                </div>
                <div id="scc-permalink-edit" style="display: none;">
                    <input type="text" id="scc_custom_permalink" name="scc_custom_permalink" value="<?php echo esc_attr($permalink); ?>" />
                    <button type="button" id="scc-permalink-save" class="button button-small"><?php esc_html_e('Save', 'simple-custom-code'); ?></button>
                    <button type="button" id="scc-permalink-cancel" class="button button-small"><?php esc_html_e('Cancel', 'simple-custom-code'); ?></button>
                </div>


            </div>
        </div>

        <style>
            #scc-permalink-box {
                margin-bottom: 20px;
            }

            #scc-permalink-box .inside {
                padding: 10px 15px;
            }
        </style>
<?php
    }

    /**
     * Save meta boxes data
     */
    public function save_meta_boxes($post_id)
    {
        // Verify nonce
        if (! isset($_POST['scc_meta_nonce']) || ! wp_verify_nonce($_POST['scc_meta_nonce'], 'scc_save_meta_boxes')) {
            return;
        }

        // Check if user has permission to edit the post
        if (! current_user_can('edit_post', $post_id)) {
            return;
        }

        // Check if not an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check post type
        if ('simple-custom-code' !== get_post_type($post_id)) {
            return;
        }

        // Save meta fields
        $meta_fields = array(
            'scc_active' => 'boolean',
            'scc_code_type' => 'string',
            'scc_loading_method' => 'string',
            'scc_loading_locations' => 'array',
            'scc_priority' => 'int',
            'scc_code_position' => 'string',
            'scc_linking_type' => 'string',
            'scc_custom_permalink' => 'string'
        );

        foreach ($meta_fields as $field => $type) {
            $value = isset($_POST[$field]) ? $_POST[$field] : '';

            switch ($type) {
                case 'boolean':
                    $value = (bool) $value;
                    break;
                case 'int':
                    $value = (int) $value;
                    break;
                case 'array':
                    $value = is_array($value) ? $value : array();
                    break;
                default:
                    $value = sanitize_text_field($value);
            }

            update_post_meta($post_id, '_scc_' . str_replace('scc_', '', $field), $value);
        }

        // Save code content to file
        if (isset($_POST['scc_code_content'])) {
            $file_manager = SCC_File_Manager::get_instance();
            $file_manager->save_code_content($post_id, wp_unslash($_POST['scc_code_content']));
        }

        // Clear cache after saving
        $cache = SCC_Cache::get_instance();
        $cache->clear_all_cache();
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook)
    {
        global $post_type;

        if ('simple-custom-code' !== $post_type || ! in_array($hook, array('post-new.php', 'post.php'))) {
            return;
        }

        wp_enqueue_script(
            'scc-admin-metaboxes',
            SCC_PLUGIN_URL . 'assets/js/admin-metaboxes.js',
            array('jquery'),
            SCC_VERSION,
            true
        );

        wp_enqueue_script(
            'scc-admin-ai',
            SCC_PLUGIN_URL . 'assets/js/admin-ai.js',
            array('jquery'),
            SCC_VERSION,
            true
        );

        wp_localize_script('scc-admin-ai', 'sccaiGenerator', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('scc_ai_generate_nonce'),
        ]);

        wp_localize_script(
            'scc-admin-metaboxes',
            'ccmMetaboxes',
            array(
                'nonce' => wp_create_nonce('scc_admin_nonce'),
                'strings' => array(
                    'remove' => __('Remove', 'simple-custom-code'),
                    'confirm_remove' => __('Are you sure you want to remove this condition?', 'simple-custom-code')
                )
            )
        );

        wp_enqueue_style(
            'scc-admin-metaboxes',
            SCC_PLUGIN_URL . 'assets/css/admin-metaboxes.css',
            array(),
            SCC_VERSION
        );


        wp_enqueue_style(
            'scc-admin-ai',
            SCC_PLUGIN_URL . 'assets/css/admin-ai.css',
            array(),
            SCC_VERSION
        );
        // loading code mirror
        $codemirror_base = SCC_PLUGIN_URL . 'assets/editor/';

        if ($hook == 'post-new.php' || $hook == 'post.php') {
            wp_deregister_script('wp-codemirror');
            wp_enqueue_script('scc-codemirror', $codemirror_base . '/lib/editor.js', array('jquery'), SCC_VERSION, false);
            wp_enqueue_style('scc-codemirror', $codemirror_base . '/lib/editor.css', array(), SCC_VERSION);

            $current_options = scc_get_settings();
            if (!empty($current_options['editor_template'])) {
                wp_enqueue_style('scc-codemirror-' . esc_attr($current_options['editor_template']), $codemirror_base . '/theme/' . $current_options['editor_template'] . '.css', array(), SCC_VERSION);
            }

            // Add the language modes
            $codemirror_base_modes = $codemirror_base . '/mode/';
            wp_enqueue_script('scc-xml', $codemirror_base_modes . 'xml/xml.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-js', $codemirror_base_modes . 'javascript/javascript.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-css', $codemirror_base_modes . 'css/css.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-htmlmixed', $codemirror_base_modes . 'htmlmixed/htmlmixed.js', array('scc-codemirror'), SCC_VERSION, false);

            $codemirror_base_addon = $codemirror_base . '/addon/';
            wp_enqueue_script('scc-closebrackets', $codemirror_base_addon . 'edit/closebrackets.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-matchbrackets', $codemirror_base_addon . 'edit/matchbrackets.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-matchtags', $codemirror_base_addon . 'edit/matchtags.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-closetag', $codemirror_base_addon . 'edit/closetag.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-dialog', $codemirror_base_addon . 'dialog/dialog.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-search', $codemirror_base_addon . 'search/search.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-searchcursor', $codemirror_base_addon . 'search/searchcursor.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-jump-to-line', $codemirror_base_addon . 'search/jump-to-line.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-fullscreen', $codemirror_base_addon . 'display/fullscreen.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_style('scc-dialog', $codemirror_base_addon . 'dialog/dialog.css', array(), SCC_VERSION);
            wp_enqueue_script('scc-comment', $codemirror_base_addon . 'comment/comment.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-active-line', $codemirror_base_addon . 'selection/active-line.js', array('scc-codemirror'), SCC_VERSION, false);

            // Beatify
            wp_enqueue_script('scc-beautify', SCC_PLUGIN_URL . 'assets/beautify/beautify.min.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-beautify-css', SCC_PLUGIN_URL . 'assets/beautify/beautify-css.min.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-beautify-html', SCC_PLUGIN_URL . 'assets/beautify/beautify-html.min.js', array('scc-codemirror'), SCC_VERSION, false);


            // Hint Addons
            wp_enqueue_script('scc-hint', $codemirror_base_addon . 'hint/show-hint.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-hint-js', $codemirror_base_addon . 'hint/javascript-hint.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-hint-xml', $codemirror_base_addon . 'hint/xml-hint.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-hint-html', $codemirror_base_addon . 'hint/html-hint.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-hint-css', $codemirror_base_addon . 'hint/css-hint.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-hint-anyword', $codemirror_base_addon . 'hint/anyword-hint.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_style('scc-hint', $codemirror_base_addon . 'hint/show-hint.css', array(), SCC_VERSION);

            // Lint Addons
            wp_enqueue_script('scc-lint-js', $codemirror_base_addon . 'lint/javascript-lint.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-lint-json', $codemirror_base_addon . 'lint/json-lint.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-lint-css', $codemirror_base_addon . 'lint/css-lint.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-lint-html', $codemirror_base_addon . 'lint/html-lint.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-lint-vendors-js', $codemirror_base . 'vendors/jshint.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-int-vendors-css', $codemirror_base . 'vendors/csslint.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-int-vendors-style', $codemirror_base . 'vendors/stylelint-bundle.min.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-int-vendors-html', $codemirror_base . 'vendors/htmlhint.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-int-vendors-scss', $codemirror_base . 'vendors/scsslint.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-lint', $codemirror_base_addon . 'lint/lint.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_style('scc-lint', $codemirror_base_addon . 'lint/lint.css', array(), SCC_VERSION);


            // Fold Addons
            wp_enqueue_script('scc-fold-brace', $codemirror_base_addon . 'fold/brace-fold.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-fold-comment', $codemirror_base_addon . 'fold/comment-fold.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-fold-code', $codemirror_base_addon . 'fold/foldcode.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-fold-gutter', $codemirror_base_addon . 'fold/foldgutter.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-fold-indent', $codemirror_base_addon . 'fold/indent-fold.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-fold-markdown', $codemirror_base_addon . 'fold/markdown-fold.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_script('scc-fold-xml', $codemirror_base_addon . 'fold/xml-fold.js', array('scc-codemirror'), SCC_VERSION, false);
            wp_enqueue_style('scc-fold-gutter', $codemirror_base_addon . 'fold/foldgutter.css', array(), SCC_VERSION);
        }
    }
}
