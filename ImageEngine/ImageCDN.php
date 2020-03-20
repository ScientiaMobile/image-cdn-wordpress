<?php

namespace ImageEngine;

class ImageCDN
{

    /**
     * Static constructor
     */
    public static function instance()
    {
        new self();
    }


    /**
     * Constructor
     */
    public function __construct()
    {
        // CDN rewriter hook
        add_action('template_redirect', [self::class, 'handle_rewrite_hook']);

        // Rewrite rendered content in REST API
        add_filter('the_content', [self::class, 'rewrite_the_content'], 100);

        // Resource hints
        add_action('wp_head', [self::class, 'add_head_tags'], 0);

        // Hooks
        add_action('admin_init', [self::class, 'register_textdomain']);
        add_action('admin_init', [Settings::class, 'register_settings']);
        add_action('admin_menu', [Settings::class, 'add_settings_page']);
        add_filter('plugin_action_links_'.IMAGE_CDN_BASE, [self::class, 'add_action_link']);
    }

    /**
     * Add meta tags for Client Hints and Preconnect Resource Hint.
     */
    public static function add_head_tags()
    {
        $options = self::get_options();

        echo '    <meta http-equiv="Accept-CH" content="DPR, Viewport-Width, Width, Save-Data">' . "\n";
        $host = parse_url($options['url'], PHP_URL_HOST);
        if (!empty($host)) {
            echo '    <link rel="preconnect" href="//' . $host . '">' . "\n";
        }
    }

    /**
     * Run uninstall hook
     */
    public static function handle_uninstall_hook()
    {
        delete_option('image_cdn');
    }


    /**
     * Run activation hook
     */
    public static function handle_activation_hook()
    {
        add_option(
            'image_cdn',
            [
                'url'        => get_option('home'),
                'dirs'       => 'wp-content,wp-includes',
                'excludes'   => '.php',
                'relative'   => '1',
                'https'      => '',
                'directives' => '',
            ]
        );
    }


    /**
     * Check plugin requirements
     */
    public static function image_cdn_requirements_check()
    {
        // WordPress version check
        if (version_compare($GLOBALS['wp_version'], IMAGE_CDN_MIN_WP.'alpha', '<')) {
            show_message(
                sprintf(
                    '<div class="error"><p>%s</p></div>',
                    sprintf(
                        __("CDN Enabler is optimized for WordPress %s. Please disable the plugin or upgrade your WordPress installation (recommended).", "image-cdn"),
                        IMAGE_CDN_MIN_WP
                    )
                )
            );
        }
    }


    /**
     * Register textdomain
     */
    public static function register_textdomain()
    {
        load_plugin_textdomain('image-cdn', false, 'image-cdn/lang');
    }


    /**
     * Return plugin options
     *
     * @return  array  $diff  data pairs
     */
    public static function get_options()
    {
        return wp_parse_args(
            get_option('image_cdn'),
            [
                'url'             => get_option('home'),
                'dirs'            => 'wp-content,wp-includes',
                'excludes'        => '.php',
                'relative'        => 1,
                'https'           => 0,
                'directives'      => '',
            ]
        );
    }


    /**
     * Return new rewriter
     *
     */
    public static function get_rewriter()
    {
        $options = self::get_options();

        $excludes = array_map('trim', explode(',', $options['excludes']));

        return new Rewriter(
            get_option('home'),
            $options['url'],
            $options['dirs'],
            $excludes,
            $options['relative'],
            $options['https'],
            $options['directives']
        );
    }


    /**
     * Run rewrite hook
     */
    public static function handle_rewrite_hook()
    {
        $options = self::get_options();

        // check if origin equals cdn url
        if (get_option('home') == $options['url']) {
            return;
        }

        $rewriter = self::get_rewriter();
        ob_start([&$rewriter, 'rewrite']);
    }


    /**
     * Rewrite html content
     */
    public static function rewrite_the_content($html)
    {
        $rewriter = self::get_rewriter();
        return $rewriter->rewrite($html);
    }
}
