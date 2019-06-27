<?php

namespace Alitheia\IPlugin;

interface IPluginAccess
{
    public function handle_plugin_role_cap(string $plugin_role, bool $activate);

}
