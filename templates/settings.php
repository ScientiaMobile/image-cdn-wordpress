<script src="<?php echo plugin_dir_url(IMAGE_CDN_FILE) ?>assets/utilities.js"></script>
<div class="wrap">
    <img src="<?php echo plugin_dir_url(IMAGE_CDN_FILE) ?>assets/logo.png" />
    <div class="notice notice-info">
        <p>
            This plugin is best used with <a href="https://imageengine.io/?from=wp" target="_blank">ImageEngine</a>,
            but will also work with most other CDNs. <a href="https://www.scientiamobile.com/support/" target="_blank">Support is available</a>
            to assist ImageEngine customers.
        </p>
    </div>
    <h2><?php _e("Image CDN Settings", "image-cdn"); ?></h2>
    <?php if ($options['enabled'] && !$is_runnable) { ?>
        <div class="notice notice-error">
            <p>
                <?php _e("Image CDN support is disabled because there is something wrong with your configuration.  Please verify the URL below."); ?>
            </p>
        </div>
    <?php } ?>

    <form method="post" action="options.php">
        <?php settings_fields('image_cdn') ?>

        <table class="form-table">

            <tr valign="top">
                <th scope="row">
                    <?php _e("Enabled", "image-cdn"); ?>
                </th>
                <td>
                    <fieldset>
                        <label for="image_cdn_enabled">
                            <input type="checkbox" name="image_cdn[enabled]" id="image_cdn_enabled" value="1" <?php checked(1, $options['enabled']) ?> />
                            <?php _e("Enable CDN support.", "image-cdn"); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>

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
                            <?php _e("Enter your ImageEngine (or other Image CDN) URL. For ImageEngine, this can be found in your customer vault. In most cases, this will be a scheme and a hostname, like <code>https://my-site.cdn.imgeng.in</code>.", "image-cdn"); ?>
                        </p>
                    </fieldset>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">
                    <?php _e("WordPress URL Path", "image-cdn"); ?>
                </th>
                <td>
                    <fieldset>
                        <label for="image_cdn_path">
                            <input type="text" name="image_cdn[path]" id="image_cdn_path" value="<?php echo $options['path']; ?>" size="64" class="regular-text code" />
                            <?php _e("Optional", "image-cdn"); ?>
                        </label>

                        <p class="description">
                            <?php _e("Path/subdirectory that WordPress is installed at.  For example, if WordPress is installed at <code>https://foo.bar.com/blog</code>, you would enter <code>blog</code>.  This is normally auto-detected properly, and is usually empty.", "image-cdn"); ?>
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
                            <?php _e("Optional", "image-cdn"); ?>
                        </label>

                        <p class="description">
                            <?php
                            printf(
                                __(
                                    'Enter the <a href="%s" target="_blank">ImageEngine Directives</a> to apply to all images.',
                                    'image-cdn'
                                ),
                                esc_url("https://imageengine.io/docs/implementation/directives")
                            );
                            ?>

                            <?php _e("Example: <code>/cmpr_10/s_0</code> (sets the compression to 10% and disables sharpening)", "image-cdn"); ?>
                        </p>
                    </fieldset>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
            <input type="button" name="check-cdn" id="check-cdn" class="hidden button button-primary" value="Test Configuration">
        </p>
    </form>
</div>

<div class="image-cdn-test notice notice-info hidden">
    <p>Testing CDN configuration ...</p>
</div>
<div class="image-cdn-test notice notice-success hidden">
    <p>Configuration test successful!</p>
</div>
<div class="image-cdn-test notice notice-error hidden">
    <p>Configuration test failed: <em class="image-cdn-result"></em></p>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelector('#check-cdn').addEventListener('click', () => {

            const pending = document.querySelector('.image-cdn-test.notice-info')
            const success = document.querySelector('.image-cdn-test.notice-success')
            const error = document.querySelector('.image-cdn-test.notice-error')

            pending.classList.remove('hidden')
            success.classList.add('hidden')
            error.classList.add('hidden')

            window.scrollTo({
                top: 0,
                left: 0,
                behavior: 'smooth',
            });

            const local_url = <?php echo json_encode(get_option('home')); ?>;

            const ending_slashes = new RegExp('/+$', 'g')
            let cdn_url = document.querySelector('#image_cdn_url').value.replace(ending_slashes, '')
            let path = document.querySelector('#image_cdn_path').value.replace(ending_slashes, '')
            cdn_url += path

            imageCDNCheckURLs(local_url, cdn_url)
                .then(() => {
                    pending.classList.add('hidden')
                    success.classList.remove('hidden')
                })
                .catch(e => {
                    pending.classList.add('hidden')
                    error.querySelector('.image-cdn-result').innerHTML = e.message
                    error.classList.remove('hidden')
                })
        })
    })
</script>