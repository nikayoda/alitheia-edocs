<?php

namespace Alitheia\IPlugin;

class Html implements IHtml
{
    public function debug_page_html()
    {
        ?>
        <div class="wrap">
        <h2><?Php _e(self::$plugin_name . ' Debug Log', 'alitheia_edocs'); ?></h2>
        <div id="poststuff">
            <div id="post-body">
                <?php
                if (isset($_POST['AeDoc_update_log_settings'])) {
                    $nonce = $_REQUEST['_wpnonce'];
                    if (!wp_verify_nonce($nonce, 'AeDoc_debug_log_settings')) {
                        wp_die('Error! Nonce Security Check Failed! please save the settings again.');
                    }
                    update_option('AeDoc_enable_debug', (isset($_POST["enable_debug"]) && $_POST["enable_debug"] == '1') ? '1' : '');
                    echo '<div id="message" class="updated fade"><p>' . __('Settings Saved', 'alitheia_edocs') . '!</p></div>';
                }
                if (isset($_POST['AeDoc_reset_log'])) {
                    $nonce = $_REQUEST['_wpnonce'];
                    if (!wp_verify_nonce($nonce, 'AeDoc_reset_log_settings')) {
                        wp_die('Error! Nonce Security Check Failed! please save the settings again.');
                    }
                    if (AeDoc_reset_log()) {
                        echo '<div id="message" class="updated fade"><p>' . __('Debug log file has been reset', 'alitheia_edocs') . '!</p></div>';
                    } else {
                        echo '<div id="message" class="error"><p>' . __('Debug log file could not be reset', 'alitheia_edocs') . '!</p></div>';
                    }
                }
                $real_file = AeDoc_debug_log_path;
                $content = file_get_contents($real_file);
                $content = esc_textarea($content);
                ?>
                <div id="template"><textarea cols="70" rows="25" name="AeDoc_log"
                                             id="AeDoc_log"><?php echo $content; ?></textarea></div>
                <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
                    <?php wp_nonce_field('AeDoc_debug_log_settings'); ?>
                    <table class="form-table">
                        <tbody>
                        <tr valign="top">
                            <th scope="row"><?Php _e('Enable Debug', 'alitheia_edocs'); ?></th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><span>Enable Debug</span></legend>
                                    <label for="enable_debug">
                                        <input name="enable_debug" type="checkbox"
                                               id="enable_debug" <?php if (get_option('AeDoc_enable_debug') == '1') echo ' checked="checked"'; ?>
                                               value="1">
                                        <?Php _e('Check this option if you want to enable debug', 'alitheia_edocs'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>

                        </tbody>

                    </table>
                    <p class="submit"><input type="submit" name="AeDoc_update_log_settings"
                                             id="AeDoc_update_log_settings" class="button button-primary"
                                             value="<?Php _e('Save Changes', 'alitheia_edocs'); ?>"></p>
                </form>
                <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
                    <?php wp_nonce_field('AeDoc_reset_log_settings'); ?>
                    <p class="submit"><input type="submit" name="AeDoc_reset_log" id="AeDoc_reset_log" class="button"
                                             value="<?Php _e('Reset Log', 'alitheia_edocs'); ?>"></p>
                </form>
            </div>
        </div>
        </div>

        <?php
    }

    public function html_form_code()
    {
        echo '<form action="/?tsf_generate_form=true" method="post">';
        wp_nonce_field('tsfgen_meta_box', 'tsfgen_meta_box_nonce');

        echo '<p>';
        echo 'Your Name (required) <br />';
        echo '<input type="text" name="tsf-name" pattern="[a-zA-Z0-9 ]+" value="' . (isset($_POST["tsf-name"]) ? esc_attr($_POST["tsf-name"]) : '') . '" size="40" />';
        echo '</p>';
        echo '<p>';
        echo 'Your Email (required) <br />';
        echo '<input type="email" name="tsf-email" value="' . (isset($_POST["tsf-email"]) ? esc_attr($_POST["tsf-email"]) : '') . '" size="40" />';
        echo '</p>';
        echo '<p>';
        echo 'Tel (required) <br />';
        echo '<input type="text" name="tsf-tel" pattern="[0-9]+" value="' . (isset($_POST["tsf-tel"]) ? esc_attr($_POST["tsf-tel"]) : '') . '" size="40" />';
        echo '</p>';
        echo '<p>';
        echo 'Company (required) <br />';
        echo '<input type="text" name="tsf-company" pattern="[a-zA-Z0-9 ]+"  value="' . (isset($_POST["tsf-company"]) ? esc_attr($_POST["tsf-company"]) : '') . '" size="40" />';
        echo '</p>';
        echo '<p>';
        echo 'Address (required) <br />';
        echo '<input type="text" name="tsf-address" value="' . (isset($_POST["tsf-address"]) ? esc_attr($_POST["tsf-address"]) : '') . '" size="40" />';
        echo '</p>';
        echo '<p><input type="submit" name="tsf-submitted" value="Generate Document" onClick="this.disabled=true; this.value=\'Downloading…\'; this.form.submit();"/></p>';
        echo '</form>';
    }

    //meta boxes

    public function general_settings_html() { ?>
        <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
            <?php wp_nonce_field('form_generator_general_settings'); ?>

            <table class="form-table">

                <tbody>

                <tr valign="top">
                    <th scope="row"><?Php _e('Enable Test Mode', 'alitheia_edocs'); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span>Enable Test Mode</span></legend>
                            <label for="enable_testmode">
                                <input name="enable_testmode" type="checkbox"
                                       id="enable_testmode" <?php if (get_option('form_generator_enable_testmode') == '1') echo ' checked="checked"'; ?>
                                       value="1">
                                <?Php _e('Check this option if you want to enable sandbox for testing', 'alitheia_edocs'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"><label for="paypal_email"><?Php _e('Admin Email', 'alitheia_edocs'); ?></label></th>
                    <td><input name="paypal_email" type="text" id="paypal_email"
                               value="<?php echo get_option('form_generator_email'); ?>" class="regular-text">
                        <p class="description"><?Php _e('Your admin email address', 'alitheia_edocs'); ?></p></td>
                </tr>

                </tbody>

            </table>

            <p class="submit"><input type="submit" name="form_generator_update_settings"
                                     id="form_generator_update_settings" class="button button-primary"
                                     value="<?Php _e('Save Changes', 'alitheia_edocs'); ?>"></p></form>

    <?php }
} ?>