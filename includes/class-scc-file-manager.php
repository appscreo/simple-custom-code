<?php

/**
 * File management functionality
 *
 * @package CustomCodeManager
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * SCC_File_Manager class
 */
class SCC_File_Manager
{

    /**
     * Instance of this class
     *
     * @var SCC_File_Manager
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return SCC_File_Manager
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
        add_action('wp_trash_post', array($this, 'delete_code_file'));
        add_action('before_delete_post', array($this, 'delete_code_file'));
    }

    /**
     * Get upload directory for code files
     *
     * @return string
     */
    private function get_upload_dir()
    {
        return scc_get_upload_dir();
    }

    /**
     * Get file path for a post
     *
     * @param int $post_id Post ID.
     * @param string $code_type Code type (css, js, html).
     * @param string $suffix File suffix (e.g., 'min', 'compiled').
     * @return string
     */
    public function get_file_path($post_id, $code_type = '', $suffix = '')
    {
        $upload_dir = $this->get_upload_dir();

        if (empty($code_type)) {
            $code_type = get_post_meta($post_id, '_scc_code_type', true);
            $code_type = $code_type ? $code_type : 'css';
        }

        $permalink = get_post_meta($post_id, '_scc_custom_permalink', true);
        $filename = $permalink ? $permalink : $post_id;

        $extension = $this->get_file_extension($code_type);

        if ($suffix) {
            $filename .= '-' . $suffix;
        }

        return $upload_dir . $filename . $extension;
    }

    /**
     * Get file URL for a post
     *
     * @param int $post_id Post ID.
     * @param string $code_type Code type (css, js, html).
     * @param string $suffix File suffix (e.g., 'min', 'compiled').
     * @return string
     */
    public function get_file_url($post_id, $code_type = '', $suffix = '')
    {
        $upload_url = scc_get_upload_url();

        if (empty($code_type)) {
            $code_type = get_post_meta($post_id, '_scc_code_type', true);
            $code_type = $code_type ? $code_type : 'css';
        }

        $permalink = get_post_meta($post_id, '_scc_custom_permalink', true);
        $filename = $permalink ? $permalink : $post_id;

        $extension = $this->get_file_extension($code_type);

        if ($suffix) {
            $filename .= '-' . $suffix;
        }

        return $upload_url . $filename . $extension;
    }

    /**
     * Get file extension for code type
     *
     * @param string $code_type Code type.
     * @return string
     */
    private function get_file_extension($code_type)
    {
        switch ($code_type) {
            case 'css':
                return '.css';
            case 'js':
                return '.js';
            case 'html':
                return '.html';
            default:
                return '.txt';
        }
    }

    /**
     * Save code content to file
     *
     * @param int $post_id Post ID.
     * @param string $content Code content.
     * @return bool
     */
    public function save_code_content($post_id, $content)
    {
        $code_type = get_post_meta($post_id, '_scc_code_type', true);
        $code_type = $code_type ? $code_type : 'css';

        // Create upload directory if it doesn't exist
        $upload_dir = $this->get_upload_dir();
        if (! file_exists($upload_dir)) {
            wp_mkdir_p($upload_dir);
        }

        $file_path = $this->get_file_path($post_id, $code_type);

        // Save original content
        $result = file_put_contents($file_path, $content);

        if ($result === false) {
            return false;
        }

        // Process CSS preprocessors
        if ($code_type === 'css') {
            $this->process_css_preprocessor($post_id, $content);
        }


        return true;
    }

    /**
     * Get code content from file
     *
     * @param int $post_id Post ID.
     * @return string
     */
    public function get_code_content($post_id)
    {
        $file_path = $this->get_file_path($post_id);

        if (file_exists($file_path)) {
            return file_get_contents($file_path);
        }

        return '';
    }

    /**
     * Process CSS preprocessor
     *
     * @param int $post_id Post ID.
     * @param string $content CSS content.
     */
    private function process_css_preprocessor($post_id, $content)
    {
        $preprocessor = get_post_meta($post_id, '_scc_css_preprocessor', true);

        if (! $preprocessor || $preprocessor === 'none') {
            return;
        }
        
    }

    /**
     * Create minified version of file
     *
     * @param int $post_id Post ID.
     * @param string $content Code content.
     * @param string $code_type Code type.
     */

    /**
     * Delete code file when post is trashed or deleted
     *
     * @param int $post_id Post ID.
     */
    public function delete_code_file($post_id)
    {
        if (get_post_type($post_id) !== 'simple-custom-code') {
            return;
        }

        $code_type = get_post_meta($post_id, '_scc_code_type', true);
        $code_type = $code_type ? $code_type : 'css';

        // Delete main file
        $file_path = $this->get_file_path($post_id, $code_type);
        if (file_exists($file_path)) {
            wp_delete_file($file_path);
        }

        // Delete minified file
        $min_file_path = $this->get_file_path($post_id, $code_type, 'min');
        if (file_exists($min_file_path)) {
            wp_delete_file($min_file_path);
        }

        // Delete compiled files (for CSS)
        if ($code_type === 'css') {
            $compiled_file_path = $this->get_file_path($post_id, $code_type, 'compiled');
            if (file_exists($compiled_file_path)) {
                wp_delete_file($compiled_file_path);
            }

            $compiled_min_file_path = $this->get_file_path($post_id, $code_type, 'compiled.min');
            if (file_exists($compiled_min_file_path)) {
                wp_delete_file($compiled_min_file_path);
            }
        }
    }

    /**
     * Get the appropriate file for loading based on settings
     *
     * @param int $post_id Post ID.
     * @return array
     */
    public function get_loading_file_info($post_id)
    {
        $code_type = get_post_meta($post_id, '_scc_code_type', true);
        $minify_enabled = get_post_meta($post_id, '_scc_minify_enabled', true);
        $linking_type = get_post_meta($post_id, '_scc_linking_type', true);
        $preprocessor = get_post_meta($post_id, '_scc_css_preprocessor', true);
        $css_optimized = get_post_meta($post_id, '_scc_css_optimize_enabled', true);

        $suffix = '';
        $use_compiled = false;

        // For CSS, check if we should use compiled version
        if ($code_type === 'css' && $preprocessor && $preprocessor !== 'none') {
            $use_compiled = true;
            $suffix = 'compiled';
        }

        // Add minification suffix
        if ($minify_enabled) {
            $suffix .= $use_compiled ? '.min' : 'min';
        }

        $file_path = $this->get_file_path($post_id, $code_type, $suffix);
        $file_url = $this->get_file_url($post_id, $code_type, $suffix);

        return array(
            'path' => $file_path,
            'url' => $file_url,
            'exists' => file_exists($file_path),
            'type' => $code_type,
            'linking_type' => $linking_type,
            'minified' => $minify_enabled,
            'optimized_css' => $css_optimized,
            'compiled' => $use_compiled
        );
    }

    /**
     * Get code content for output
     *
     * @param int $post_id Post ID.
     * @return string
     */
    public function get_code_for_output($post_id)
    {
        $file_info = $this->get_loading_file_info($post_id);

        if ($file_info['exists']) {
            return file_get_contents($file_info['path']);
        }

        // Fallback to original file if processed version doesn't exist
        $original_path = $this->get_file_path($post_id);
        if (file_exists($original_path)) {
            return file_get_contents($original_path);
        }

        return '';
    }

    /**
     * Clean up old files when permalink changes
     *
     * @param int $post_id Post ID.
     * @param string $old_permalink Old permalink.
     */
    public function cleanup_old_files($post_id, $old_permalink)
    {
        $code_type = get_post_meta($post_id, '_scc_code_type', true);
        $code_type = $code_type ? $code_type : 'css';
        $extension = $this->get_file_extension($code_type);
        $upload_dir = $this->get_upload_dir();

        // Files to clean up
        $files_to_delete = array(
            $upload_dir . $old_permalink . $extension,
            $upload_dir . $old_permalink . '-min' . $extension,
            $upload_dir . $old_permalink . '-compiled' . $extension,
            $upload_dir . $old_permalink . '-compiled.min' . $extension
        );

        foreach ($files_to_delete as $file) {
            if (file_exists($file)) {
                wp_delete_file($file);
            }
        }
    }

    /**
     * Get file size in human readable format
     *
     * @param int $post_id Post ID.
     * @return string
     */
    public function get_file_size($post_id)
    {
        $file_path = $this->get_file_path($post_id);

        if (! file_exists($file_path)) {
            return '0 B';
        }

        $size = filesize($file_path);
        $units = array('B', 'KB', 'MB', 'GB');

        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }

        return round($size, 2) . ' ' . $units[$i];
    }

    /**
     * Validate file permissions
     *
     * @return bool
     */
    public function validate_permissions()
    {
        $upload_dir = $this->get_upload_dir();

        if (! file_exists($upload_dir)) {
            return wp_mkdir_p($upload_dir);
        }

        return is_writable($upload_dir);
    }
}
