<?php
require 'vendor/autoload.php';


use Spipu\Html2Pdf\Html2Pdf;
use Spipu\Html2Pdf\Exception\Html2PdfException;
use Spipu\Html2Pdf\Exception\ExceptionFormatter;


/**
 * Class alitheia-forms
 * @author Me
 */
class alitheia_edocs_handler
{

    public function alitheia_edocs_page()
    {
        

        $labels = array(
            'name' => __(ucfirst(alitheia_edocs::$plugin_name), 'alitheia_edocs'),
            'singular_name' => __(ucfirst(alitheia_edocs::$plugin_name_singular), 'alitheia_edocs'),
            'menu_name' => __(ucfirst(alitheia_edocs::$plugin_name), 'alitheia_edocs'),
            'name_admin_bar' => __('company', 'alitheia_edocs'),
            'add_new' => __('Add New', 'alitheia_edocs'),
            'add_new_item' => __('Add New company', 'alitheia_edocs'),
            'new_item' => __('New company', 'alitheia_edocs'),
            'edit_item' => __('Edit company', 'alitheia_edocs'),
            'view_item' => __('View company', 'alitheia_edocs'),
            'all_items' => __('All companies', 'alitheia_edocs'),
            'search_items' => __('Search companies', 'alitheia_edocs'),
            'parent_item_colon' => __('Parent companies:', 'alitheia_edocs'),
            'not_found' => __('No companies found.', 'alitheia_edocs'),
            'not_found_in_trash' => __('No companies found in Trash.', 'alitheia_edocs')
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
            'capability_type' => 'alitheia_edocs',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => 'false',
            'capabilities' => array(
                'publish_posts' => 'publish_alitheia_edocs',
                'edit_posts' => 'edit_alitheia_edocs',
                'edit_others_posts' => 'edit_others_alitheia_edocs',
                'delete_posts' => 'delete_alitheia_edocs',
                'delete_others_posts' => 'delete_others_alitheia_edocs',
                'read_private_posts' => 'read_private_alitheia_edocs',
                'edit_post' => 'edit_custom_form',
                'delete_post' => 'delete_custom_form',
                'read_post' => 'read_custom_form',
            ),
        );

        register_post_type('alitheia_edocs', $args);

    }


    public function alitheia_edocs_columns($columns)
    {
        unset($columns['title']);
        unset($columns['date']);
        $edited_columns = array(
            'tsf_name' => __('Contact Name', 'alitheia_edocs'),
            'tsf_email' => __('E-Mail', 'alitheia_edocs'),
            'tsf_tel' => __('Tel', 'alitheia_edocs'),
            'tsf_company' => __('Company', 'alitheia_edocs'),
            'tsf_address' => __('Address', 'alitheia_edocs'),
            'date' => __('Date', 'alitheia_edocs')
        );
        return array_merge($columns, $edited_columns);
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
                    'post_type' => 'alitheia_edocs'

                );
                // Insert the post into the database

                $post_id = wp_insert_post($my_post);

                // Insert or update post metas based on form inputs
                foreach ($post_metas as $key => $meta) {
                    update_post_meta($post_id, '_' . $key, $meta);
                }
                wp_redirect('/?alitheia_edocs=' . $post_id);
                exit();
            }else{
                return;
            }
        } elseif (!empty($_GET['alitheia_edocs']) && !empty($_GET['alitheia_edocs'])) {

            try {
                $title = sanitize_text_field($_GET['alitheia_edocs']);

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
