<?php
require 'vendor/autoload.php';


use Spipu\Html2Pdf\Html2Pdf;
use Spipu\Html2Pdf\Exception\Html2PdfException;
use Spipu\Html2Pdf\Exception\ExceptionFormatter;


/**
 * Class alitheia-forms
 * @author Me
 */
class custom_forms
{

    public function alitheia_forms_page()
    {
        $labels = array(
            'name' => __('alitheia Forms', 'alitheia_forms'),
            'singular_name' => __('alitheiaForms', 'alitheia_forms'),
            'menu_name' => __('alitheia Forms', 'alitheia_forms'),
            'name_admin_bar' => __('company', 'alitheia_forms'),
            'add_new' => __('Add New', 'alitheia_forms'),
            'add_new_item' => __('Add New company', 'alitheia_forms'),
            'new_item' => __('New company', 'alitheia_forms'),
            'edit_item' => __('Edit company', 'alitheia_forms'),
            'view_item' => __('View company', 'alitheia_forms'),
            'all_items' => __('All companies', 'alitheia_forms'),
            'search_items' => __('Search companies', 'alitheia_forms'),
            'parent_item_colon' => __('Parent companies:', 'alitheia_forms'),
            'not_found' => __('No companies found.', 'alitheia_forms'),
            'not_found_in_trash' => __('No companies found in Trash.', 'alitheia_forms')
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'exclude_from_search' => true,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => false,
            'capability_type' => 'custom_forms',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => 'false',
            'capabilities' => array(
                'publish_posts' => 'publish_custom_forms',
                'edit_posts' => 'edit_custom_forms',
                'edit_others_posts' => 'edit_others_custom_forms',
                'delete_posts' => 'delete_custom_forms',
                'delete_others_posts' => 'delete_others_custom_forms',
                'read_private_posts' => 'read_private_custom_forms',
                'edit_post' => 'edit_custom_form',
                'delete_post' => 'delete_custom_form',
                'read_post' => 'read_custom_form',
            ),
        );

        register_post_type('alitheia_forms', $args);

    }


    public function alitheia_forms_columns($columns)
    {
        unset($columns['title']);
        unset($columns['date']);
        $edited_columns = array(
            'tsf_name' => __('Contact Name', 'alitheia_forms'),
            'tsf_email' => __('E-Mail', 'alitheia_forms'),
            'tsf_tel' => __('Tel', 'alitheia_forms'),
            'tsf_company' => __('Company', 'alitheia_forms'),
            'tsf_address' => __('Address', 'alitheia_forms'),
            'date' => __('Date', 'alitheia_forms')
        );
        return array_merge($columns, $edited_columns);
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
    public function alitheia_forms_meta_box($post)
    {
        $tsf_name = get_post_meta($post->ID, '_tsf_name', true);
        $tsf_tel = get_post_meta($post->ID, '_tsf_tel', true);
        $tsf_email = get_post_meta($post->ID, '_tsf_email', true);
        $tsf_company = get_post_meta($post->ID, '_tsf_company', true);
        $tsf_address = get_post_meta($post->ID, '_tsf_address', true);
        // Add an nonce field so we can check for it later.
        wp_nonce_field('tsfgen_meta_box', 'tsfgen_meta_box_nonce');
        ?>
        <table class="form-table">
            <tbody>
            <tr valign="top">
                <th scope="row"><label for="tsf_name"><?php _e('Full name', 'alitheia_forms'); ?></label></th>
                <td><input name="tsf_name" type="text" id="tsf_name" value="<?php echo $tsf_name; ?>"
                           class="regular-text">
                    <p class="description">Enter your name (John Doe)</p></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="tsf_email"><?php _e('E-mail', 'alitheia_forms'); ?></label></th>
                <td><input name="tsf_email" type="text" id="tsf_email" value="<?php echo $tsf_email; ?>"
                           class="regular-text">
                    <p class="description">Enter email ( john.doe@icloud.com ) </p></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="tsf_tel"><?php _e('Phone Number', 'alitheia_forms'); ?></label></th>
                <td><input name="tsf_tel" type="text" id="tsf_tel" value="<?php echo $tsf_tel; ?>"
                           class="regular-text">
                    <p class="description">Enter phone number ( Country code + region + number, for example 1xxxxxxxxx)</p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="_tsf_company"><?php _e('Company name', 'alitheia_forms'); ?></label></th>
                <td><input name="tsf_company" type="text" id="tsf_company" value="<?php echo $tsf_company; ?>"
                           class="regular-text">
                    <p class="description">Enter your company name (Apple, inc)</p></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="tsf_address"><?php _e('Address', 'alitheia_forms'); ?></label></th>
                <td><input name="tsf_address" type="text" id="tsf_address" value="<?php echo $tsf_address; ?>"
                           class="regular-text">
                    <p class="description">Enter your address</p></td>
            </tr>
            </tbody>

        </table>

        <?php
    }

    public function form_generator_custom_column($column, $post_id)
    {
        switch ($column) {
            case 'title' :
                echo get_post_meta($post_id, '_tsf_company', true);
                break;
            case 'tsf_name' :
                echo get_post_meta($post_id, '_tsf_name', true);
                break;
            case 'tsf_email' :
                echo get_post_meta($post_id, '_tsf_email', true);
                break;
            case 'tsf_tel' :
                echo get_post_meta($post_id, '_tsf_tel', true);
                break;
            case 'tsf_address' :
                echo get_post_meta($post_id, '_tsf_address', true);
                break;
            case 'tsf_company' :
                echo get_post_meta($post_id, '_tsf_company', true);
                break;
        }
    }

    public function form_generator_save_meta_box_data($post_id)
    {
        global $wpdb;
        global $post;

        /*
         * We need to verify this came from our screen and with proper authorization,
         * because the save_post action can be triggered at other times.
         */

        $post_metas = array(
            'tsf_name' => sanitize_text_field($_POST['tsf_name']),
            'tsf_tel' => sanitize_text_field($_POST['tsf_tel']),
            'tsf_company' => sanitize_text_field($_POST['tsf_company']),
            'tsf_address' => sanitize_text_field($_POST['tsf_address']),
            'tsf_email' => sanitize_text_field($_POST['tsf_email'])
        );

        foreach ($post_metas as $key => $meta) {
            if (isset($_POST[$key]) && !empty($_POST[$key])) {
                trigger_error('All fields are necessary.');
                return;
            }
        }

        // Check if our nonce is set.
        if (!isset($_POST['tsfgen_meta_box_nonce'])) {
            return;
        }
        // Verify that the nonce is valid.
        if (!wp_verify_nonce($_POST['tsfgen_meta_box_nonce'], 'tsfgen_meta_box')) {
            return;
        }

        // Check the user's permissions.
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if ($post_id == null || empty($_POST))
            return;

        if (empty($post))
            $post = get_post($post_id);

        $title = sanitize_text_field($_POST['tsf_company']);
        $where = array('ID' => $post_id);
        $wpdb->update($wpdb->posts, array('post_title' => $title), $where);


        // Insert or update post metas based on form inputs
        foreach ($post_metas as $key => $meta) {
            update_post_meta($post_id, '_' . $key, $meta);
        }

    }


    public function get_submited_params()
    {
        global $wpdb;
        global $post;
        if (!empty($_GET['tsf_generate_form']) && $_GET['tsf_generate_form'] == 'true') {
            $form_full = true;
            $post_metas_check = array(
                'tsf-name' => sanitize_text_field($_POST['tsf-name']),
                'tsf-tel' => sanitize_text_field($_POST['tsf-tel']),
                'tsf-company' => sanitize_text_field($_POST['tsf-company']),
                'tsf-address' => sanitize_text_field($_POST['tsf-address']),
                'tsf-email' => sanitize_text_field($_POST['tsf-email'])
            );
            $post_metas = array(
                'tsf_name' => sanitize_text_field($_POST['tsf-name']),
                'tsf_tel' => sanitize_text_field($_POST['tsf-tel']),
                'tsf_company' => sanitize_text_field($_POST['tsf-company']),
                'tsf_address' => sanitize_text_field($_POST['tsf-address']),
                'tsf_email' => sanitize_text_field($_POST['tsf-email'])
            );
            foreach ($post_metas_check as $key => $value)
            {
                if(!isset($_POST[$key]) || empty($_POST[$key]))
                {
                    $form_full = false;
                }
            }

            if($form_full){


                $my_post = array(
                    'post_title' => sanitize_text_field($_POST['tsf-company']),
                    'post_content' => sanitize_text_field($_POST['tsf-company']),
                    'post_status' => 'publish',
                    'post_author' => 1,
                    //'post_category' => array(8, 39),
                    'post_type' => 'alitheia_forms'

                );
                // Insert the post into the database

                $post_id = wp_insert_post($my_post);

                // Insert or update post metas based on form inputs
                foreach ($post_metas as $key => $meta) {
                    update_post_meta($post_id, '_' . $key, $meta);
                }
                wp_redirect('/?alitheia_forms=' . $post_id);
                exit();
            }else{
                return;
            }
        } elseif (!empty($_GET['alitheia_forms']) && !empty($_GET['alitheia_forms'])) {

            try {
                $title = sanitize_text_field($_GET['alitheia_forms']);

                $meta = $wpdb->get_results("SELECT meta_key, meta_value FROM {$wpdb->prefix}postmeta WHERE `post_id` = '" . $title . "'");

                $metadata = array(
                    '_tsf_name' => '',
                    '_tsf_email' => '',
                    '_tsf_company' => '',
                    '_tsf_address' => '',
                    '_tsf_tel' => '',
                );

                foreach ($meta as $meta_key => $meta_value) {
                    $metadata[$meta_value->meta_key] = $meta_value->meta_value;
                }

                ob_start();
                include dirname(__FILE__) . '/template/template.php';
                $content = ob_get_clean();
                foreach ($metadata as $meta_key => $meta_value) {

                    $content = str_replace('{{'.$meta_key.'}}', $meta_value, $content);
                }
                //print_r($content);
                //die();
                $html2pdf = new Html2Pdf('P', 'A4', 'en');
                $html2pdf->setTestTdInOnePage(false);

                $html2pdf->writeHTML($content);
                $html2pdf->Output('document_name.pdf', "D");
                die();
            } catch (Html2PdfException $e) {
                $html2pdf->clean();
                $formatter = new ExceptionFormatter($e);
                echo $formatter->getHtmlMessage();
            }


            die();
        }


    }


    public function tsf_generate()
    {
        ob_start();
        $this->html_form_code();

        return ob_get_clean();

    }
}

add_action('save_post', 'form_generator_save_meta_box_data', 10, 1);
