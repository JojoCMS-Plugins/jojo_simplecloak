<?php

foreach (Jojo::listPlugins('external/simplecloak/simple_cloak_v2.inc.php') as $pluginfile) {
    require_once($pluginfile);
}
$simplecloakupdated = SimpleCloakV2::updateAll();