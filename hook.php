<?php

use GlpiPlugin\Usernotifications\Manager;

function plugin_usernotifications_install(): bool { return Manager::install(); }
function plugin_usernotifications_uninstall(): bool { return Manager::uninstall(); }