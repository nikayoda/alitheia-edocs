<?php


namespace Alitheia\Access;


class PluginAccess
{

    /**
     * Handling new role for specific users to have access to alitheia document generation plugin
     *
     */
    private function handle_plugin_role_cap(string $plugin_role, bool $activate){

        // Check plugin for conflicts
        if(!is_plugin_active(__FILE__) && gettype(get_role( $plugin_role )) === "object" && $activate !== false){
            wp_die("We have role name conflict, aborting plugin activation!");
        }

        if($activate){
            if(($role = add_role( $plugin_role, __(ucfirst($plugin_role))." ".__("Member"),
                array(
                    'read'          => true,
                    'edit_posts'    => true
                )
            ))){
                $role->add_cap($this->plugin_capability_name);
            }else{
                $role->remove_cap($his->plugin_capability_name);
            }

        }else{
            remove_role($plugin_role);
        }


    }
}