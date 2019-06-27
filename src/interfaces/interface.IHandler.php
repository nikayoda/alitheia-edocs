<?php

namespace Alitheia\IPlugin;

interface IHandler
{
    public function plugin_page();

    public function plugin_columns($columns);
    public function plugin_custom_column($column, $post_id);
    public function plugin_save_meta_box_data($post_id);
    public function get_submited_params();
    public function tsf_generate();

}
