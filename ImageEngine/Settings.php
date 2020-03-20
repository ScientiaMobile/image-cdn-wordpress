<?php

namespace ImageEngine;

class Settings
{

    /**
     * register settings
     */
    public static function register_settings()
    {
        register_setting('image_cdn', 'image_cdn', [self::class, 'validate_settings']);
    }


    /**
     * validation of settings
     *
     * @param   array  $data  array with form data
     * @return  array         array with validated values
     */
    public static function validate_settings($data)
    {
        if (!isset($data['relative'])) {
            $data['relative'] = 0;
        }

        if (!isset($data['https'])) {
            $data['https'] = 0;
        }

        $data['url'] = rtrim($data['url'], '/');

        $parts = parse_url($data['url']);
        if (!isset($parts['scheme']) || !isset($parts['host'])) {
            add_settings_error('url', 'url', 'Invalid URL: Missing scheme (<code>http://</code> or <code>https://</code>) or hostname');
        } else {

            // make sure there is a valid scheme
            if (!in_array($parts['scheme'], ['http', 'https'])) {
                add_settings_error('url', 'url', 'Invalid URL: Must begin with <code>http://</code> or <code>https://</code>');
            }

            // make sure the host is resolves
            if (!filter_var($parts['host'], FILTER_VALIDATE_IP)) {
                $ip = gethostbyname($parts['host']);
                if ($ip == $parts['host']) {
                    add_settings_error('url', 'url', 'Invalid URL: Could not resolve hostname');
                }
            }
        }

        return [
            'url'      => esc_url($data['url']),
            'dirs'     => esc_attr($data['dirs']),
            'excludes' => esc_attr($data['excludes']),
            'relative' => (int) ($data['relative']),
            'https'    => (int) ($data['https']),
            'directives' => self::clean_directives($data['directives']),
        ];
    }

    /**
     * clean the ImageEngine Directives
     */
    public static function clean_directives($directives)
    {
        $directives = preg_replace('#.*imgeng=/+?#', '', $directives);
        $directives = trim($directives);

        // ensure there is one leading "/" and none trailing
        $directives = trim($directives, "/");
        $directives = '/' . $directives;

        return $directives;
    }


    /**
     * add settings page
     */
    public static function add_settings_page()
    {
        $page = add_options_page('Image CDN', 'Image CDN', 'manage_options', 'image_cdn', [self::class, 'settings_page']);
    }


    /**
     * settings page
     */
    public static function settings_page()
    {
        $options = ImageCDN::get_options(); ?>
        <div class="wrap">
            <img src="<?php echo plugin_dir_url(IMAGE_CDN_FILE) ?>assets/logo.png"/>
            <blockquote>This plugin is best used with <a href="https://imageengine.io/?from=wp" target="_blank">ImageEngine</a>, but will also with with most other CDNs.</blockquote>
            <h2><?php _e("Image CDN Settings", "image-cdn"); ?></h2>

            <form method="post" action="options.php">
                <?php settings_fields('image_cdn') ?>

                <table class="form-table">

                    <tr valign="top">
                        <th scope="row">
                            <?php _e("CDN URL", "image-cdn"); ?>
                        </th>
                        <td>
                            <fieldset>
                                <label for="image_cdn_url">
                                    <input type="text" name="image_cdn[url]" id="image_cdn_url" value="<?php echo $options['url']; ?>" size="64" class="regular-text code" />
                                </label>

                                <p class="description">
                                    <?php _e("Enter your ImageEngine (or other Image CDN) URL.<br>For ImageEngine, this can be found in your customer vault.<br>In most cases, this will be a scheme and a hostname, like <code>https://my-site.cdn.imgeng.in</code>.", "image-cdn"); ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <?php _e("Included Directories", "image-cdn"); ?>
                        </th>
                        <td>
                            <fieldset>
                                <label for="image_cdn_dirs">
                                    <input type="text" name="image_cdn[dirs]" id="image_cdn_dirs" value="<?php echo $options['dirs']; ?>" size="64" class="regular-text code" />
                                    <?php _e("Default: <code>wp-content,wp-includes</code>", "image-cdn"); ?>
                                </label>

                                <p class="description">
                                    <?php _e("Assets in these directories will be pointed to the CDN URL. Enter the directories separated by", "image-cdn"); ?> <code>,</code>
                                </p>
                            </fieldset>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <?php _e("Exclusions", "image-cdn"); ?>
                        </th>
                        <td>
                            <fieldset>
                                <label for="image_cdn_excludes">
                                    <input type="text" name="image_cdn[excludes]" id="image_cdn_excludes" value="<?php echo $options['excludes']; ?>" size="64" class="regular-text code" />
                                    <?php _e("Default: <code>.php</code>", "image-cdn"); ?>
                                </label>

                                <p class="description">
                                    <?php _e("Enter the exclusions (directories or extensions) separated by", "image-cdn"); ?> <code>,</code>
                                </p>
                            </fieldset>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <?php _e("Relative Path", "image-cdn"); ?>
                        </th>
                        <td>
                            <fieldset>
                                <label for="image_cdn_relative">
                                    <input type="checkbox" name="image_cdn[relative]" id="image_cdn_relative" value="1" <?php checked(1, $options['relative']) ?> />
                                    <?php _e("Enable CDN for relative paths (default: enabled).", "image-cdn"); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <?php _e("CDN HTTPS Support", "image-cdn"); ?>
                        </th>
                        <td>
                            <fieldset>
                                <label for="image_cdn_https">
                                    <input type="checkbox" name="image_cdn[https]" id="image_cdn_https" value="1" <?php checked(1, $options['https']) ?> />
                                    <?php _e("Enable CDN for HTTPS connections (default: disabled).", "image-cdn"); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <?php _e("ImageEngine Directives", "image-cdn"); ?>
                        </th>
                        <td>
                            <fieldset>
                                <label for="image_cdn_directives">
                                    <input type="text" name="image_cdn[directives]" id="image_cdn_directives" value="<?php echo $options['directives']; ?>" size="64" class="regular-text code" />
                                </label>

                                <p class="description">
                                    <?php printf(
    __(
                                            '(optional) Enter the <a href="%s">ImageEngine Directives</a> to apply to all images.',
                                            'image-cdn'
                                        ),
    esc_url("https://imageengine.io/docs/implementation/directives")
); ?>
                                    <br>
                                    <?php _e("Example: <code>/cmpr_10/s_0</code> (sets the compression to 10% and disables sharpening)", "image-cdn"); ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>
                </table>

                <?php submit_button() ?>
            </form>
        </div><?php
    }
}
