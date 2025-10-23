<?php

/**
 * Cache functionality
 *
 * @package CustomCodeManager
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * SCC_Cache class
 */
class SCC_Cache
{

    /**
     * Instance of this class
     *
     * @var SCC_Cache
     */
    private static $instance = null;

    /**
     * Cache directory
     *
     * @var string
     */
    private $cache_dir;

    /**
     * Get instance
     *
     * @return SCC_Cache
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
        $this->cache_dir = scc_get_upload_dir() . 'cache/';
        $this->ensure_cache_directory();
    }

    /**
     * Ensure cache directory exists
     */
    private function ensure_cache_directory()
    {
        
    }

    /**
     * Check if cache is enabled
     *
     * @return bool
     */
    public function is_cache_enabled()
    {
        $settings = scc_get_settings();
        return false;
    }

    /**
     * Get cache file path
     *
     * @param string $url Current URL.
     * @param string $position Code position (head or footer).
     * @param string $type Cache type (css, js, css_js).
     * @return string
     */
    private function get_cache_file_path($url, $position, $type)
    {
        $url_hash = md5($url);
        $filename = sprintf('%s-%s-%s', $url_hash, $position, $type);

        $settings = scc_get_settings();
        $extension = '';

        if ($type === 'css') {
            $extension = '.css';
        } elseif ($type === 'js') {
            $extension = '.js';
        } elseif ($type === 'css_js') {
            $extension = '.html';
        }

        return $this->cache_dir . $filename . $extension;
    }

    /**
     * Get cache file URL
     *
     * @param string $url Current URL.
     * @param string $position Code position (head or footer).
     * @param string $type Cache type (css, js, css_js).
     * @return string
     */
    private function get_cache_file_url($url, $position, $type)
    {
        $url_hash = md5($url);
        $filename = sprintf('%s-%s-%s', $url_hash, $position, $type);

        $extension = '';
        if ($type === 'css') {
            $extension = '.css';
        } elseif ($type === 'js') {
            $extension = '.js';
        } elseif ($type === 'css_js') {
            $extension = '.html';
        }

        return scc_get_upload_url() . 'cache/' . $filename . $extension;
    }

    /**
     * Get cached content
     *
     * @param string $url Current URL.
     * @param string $position Code position (head or footer).
     * @param string $type Cache type (css, js, css_js).
     * @return string|false
     */
    public function get_cached_content($url, $position, $type)
    {
        if (! $this->is_cache_enabled()) {
            return false;
        }

        $cache_file = $this->get_cache_file_path($url, $position, $type);

        if (file_exists($cache_file)) {
            return file_get_contents($cache_file);
        }

        return false;
    }

    /**
     * Set cached content
     *
     * @param string $url Current URL.
     * @param string $position Code position (head or footer).
     * @param string $type Cache type (css, js, css_js).
     * @param array $codes Array of code data.
     * @return bool
     */
    public function set_cached_content($url, $position, $type, $codes)
    {
        if (! $this->is_cache_enabled()) {
            return false;
        }

        $settings = scc_get_settings();
        $cache_method = $settings['cache_method'];
        $cache_file = $this->get_cache_file_path($url, $position, $type);

        $content = '';

        if ($cache_method === 'file') {
            // Create separate cache files for CSS and JS
            if ($type === 'css' || $type === 'js') {
                $content = $this->combine_codes($codes, $type);
            } else {
                // For mixed content, create HTML with file references
                $content = $this->create_mixed_cache_html($codes, $url, $position);
            }
        } else {
            // Inline method - combine all codes into HTML
            $content = $this->create_inline_cache_html($codes);
        }

        $this->ensure_cache_directory();
        return file_put_contents($cache_file, $content) !== false;
    }

    /**
     * Combine codes of same type
     *
     * @param array $codes Array of code data.
     * @param string $type Code type (css or js).
     * @return string
     */
    private function combine_codes($codes, $type)
    {
        $combined_content = '';
        $file_manager = SCC_File_Manager::get_instance();

        foreach ($codes as $code) {
            if ($code['type'] !== $type) {
                continue;
            }

            $content = $file_manager->get_code_for_output($code['id']);

            if (! empty($content)) {
                $combined_content .= "/* Code ID: {$code['id']} - {$code['title']} */\n";
                $combined_content .= $content . "\n\n";
            }
        }

        return $combined_content;
    }

    /**
     * Create mixed cache HTML with file references
     *
     * @param array $codes Array of code data.
     * @param string $url Current URL.
     * @param string $position Code position.
     * @return string
     */
    private function create_mixed_cache_html($codes, $url, $position)
    {
        $html = '';
        $css_codes = array();
        $js_codes = array();
        $html_codes = array();

        $settings = scc_get_settings();
        $cache_optimized = $settings['cache_optimized'];
        $disable_comments = $settings['code_disable_debug_comments'];

        // Separate codes by type
        foreach ($codes as $code) {
            switch ($code['type']) {
                case 'css':
                    $css_codes[] = $code;
                    break;
                case 'js':
                    $js_codes[] = $code;
                    break;
                case 'html':
                    $html_codes[] = $code;
                    break;
            }
        }

        // Create CSS cache file and reference
        if (! empty($css_codes)) {
            $css_cache_file = $this->get_cache_file_path($url, $position, 'css');
            $css_content = $this->combine_codes($css_codes, 'css');
            file_put_contents($css_cache_file, $css_content);

            $css_url = $this->get_cache_file_url($url, $position, 'css');

            if ($cache_optimized == 'css' || $cache_optimized == 'css_js') {
                $html .= '<link rel="preload" href="' . esc_url($css_url) . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'" />' . "\n";
            } else {
                $html .= '<link rel="stylesheet" href="' . esc_url($css_url) . '" type="text/css" media="all" />' . "\n";
            }
        }

        // Create JS cache file and reference
        if (! empty($js_codes)) {
            $js_cache_file = $this->get_cache_file_path($url, $position, 'js');
            $js_content = $this->combine_codes($js_codes, 'js');
            file_put_contents($js_cache_file, $js_content);

            $js_url = $this->get_cache_file_url($url, $position, 'js');
            $html .= '<script src="' . esc_url($js_url) . '"' . ($cache_optimized == 'js' || $cache_optimized == 'css_js' ? ' defer' : '') . '></script>' . "\n";
        }

        // Add HTML codes directly
        if (! empty($html_codes)) {
            $file_manager = SCC_File_Manager::get_instance();
            foreach ($html_codes as $code) {
                $content = $file_manager->get_code_for_output($code['id']);
                if (! empty($content)) {
                    if (!$disable_comments) {
                        $html .= "<!-- Simple Custom Code ID: {$code['id']} - {$code['title']} -->\n";
                    }
                    $html .= $content . "\n\n";
                }
            }
        }

        return $html;
    }

    /**
     * Create inline cache HTML
     *
     * @param array $codes Array of code data.
     * @return string
     */
    private function create_inline_cache_html($codes)
    {
        $html = '';
        $file_manager = SCC_File_Manager::get_instance();

        $settings = scc_get_settings();
        $disable_comments = $settings['code_disable_debug_comments'];

        foreach ($codes as $code) {
            $content = $file_manager->get_code_for_output($code['id']);

            if (empty($content)) {
                continue;
            }

            if (!$disable_comments) {
                $html .= "<!-- Simple Custom Code ID: {$code['id']} - {$code['title']} -->\n";
            }

            switch ($code['type']) {
                case 'css':
                    $html .= '<style type="text/css">' . "\n" . $content . "\n" . '</style>' . "\n\n";
                    break;
                case 'js':
                    $html .= '<script type="text/javascript">' . "\n" . $content . "\n" . '</script>' . "\n\n";
                    break;
                case 'html':
                    $html .= $content . "\n\n";
                    break;
            }
        }

        return $html;
    }

    /**
     * Clear cache for specific URL and position
     *
     * @param string $url Current URL.
     * @param string $position Code position (head or footer).
     * @return bool
     */
    public function clear_cache($url, $position)
    {
        $settings = scc_get_settings();
        $cache_types = array();

        switch ($settings['cache_type']) {
            case 'css_js':
                $cache_types = array('css', 'js', 'css_js');
                break;
            case 'css':
                $cache_types = array('css');
                break;
            case 'js':
                $cache_types = array('js');
                break;
        }

        $cleared = true;

        foreach ($cache_types as $type) {
            $cache_file = $this->get_cache_file_path($url, $position, $type);
            if (file_exists($cache_file)) {
                $cleared = $cleared && wp_delete_file($cache_file);
            }
        }

        return $cleared;
    }

    /**
     * Clear all cache files
     *
     * @return bool
     */
    public function clear_all_cache()
    {
        if (! file_exists($this->cache_dir)) {
            return true;
        }

        $files = glob($this->cache_dir . '*');
        $cleared = true;

        foreach ($files as $file) {
            if (is_file($file)) {
                $cleared = $cleared && wp_delete_file($file);
            }
        }

        return $cleared;
    }

    /**
     * Get cache statistics
     *
     * @return array
     */
    public function get_cache_stats()
    {
        $stats = array(
            'total_files' => 0,
            'total_size' => 0,
            'css_files' => 0,
            'js_files' => 0,
            'html_files' => 0
        );

        if (! file_exists($this->cache_dir)) {
            return $stats;
        }

        $files = glob($this->cache_dir . '*');

        foreach ($files as $file) {
            if (is_file($file)) {
                $stats['total_files']++;
                $stats['total_size'] += filesize($file);

                $extension = pathinfo($file, PATHINFO_EXTENSION);
                switch ($extension) {
                    case 'css':
                        $stats['css_files']++;
                        break;
                    case 'js':
                        $stats['js_files']++;
                        break;
                    case 'html':
                        $stats['html_files']++;
                        break;
                }
            }
        }

        return $stats;
    }

    /**
     * Format file size
     *
     * @param int $bytes File size in bytes.
     * @return string
     */
    public function format_file_size($bytes)
    {
        $units = array('B', 'KB', 'MB', 'GB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if cache needs to be regenerated
     *
     * @param string $url Current URL.
     * @param string $position Code position.
     * @param string $type Cache type.
     * @param int $max_age Maximum cache age in seconds.
     * @return bool
     */
    public function needs_regeneration($url, $position, $type, $max_age = 3600)
    {
        $cache_file = $this->get_cache_file_path($url, $position, $type);

        if (! file_exists($cache_file)) {
            return true;
        }

        $file_time = filemtime($cache_file);
        return (time() - $file_time) > $max_age;
    }

    /**
     * Optimize cache files
     *
     * @return bool
     */
    public function optimize_cache()
    {
        if (! file_exists($this->cache_dir)) {
            return true;
        }

        $files = glob($this->cache_dir . '*');
        $old_files = 0;

        // Remove files older than 7 days
        $cutoff_time = time() - (7 * 24 * 60 * 60);

        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoff_time) {
                wp_delete_file($file);
                $old_files++;
            }
        }

        return $old_files;
    }
}
