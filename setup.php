<?php

use Glpi\Plugin\Hooks;

define('PLUGIN_USERNOTIFICATIONS_VERSION', '1.0.12');
define('PLUGIN_USERNOTIFICATIONS_MIN_GLPI_VERSION', '11.0.1');
define('PLUGIN_USERNOTIFICATIONS_MAX_GLPI_VERSION', '11.1.0');

/**
 * Asset-only initialization. Event listeners are intentionally not registered
 * here: a faulty listener must never prevent a GLPI page from loading.
 */
function plugin_init_usernotifications(): void
{
    global $PLUGIN_HOOKS;

    if (!Plugin::isPluginActive('usernotifications') || Session::getLoginUserID() <= 0) {
        return;
    }

    $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['usernotifications'] = ['js/notification-bell.js'];
    $PLUGIN_HOOKS[Hooks::ADD_CSS]['usernotifications'] = ['css/notification-bell.css'];
}

function plugin_version_usernotifications(): array
{
    return [
        'name' => 'Sino de notificações',
        'version' => PLUGIN_USERNOTIFICATIONS_VERSION,
        'author' => '@DevGuijas - Github',
        'license' => 'GPL-3.0-or-later',
        'requirements' => ['glpi' => ['min' => PLUGIN_USERNOTIFICATIONS_MIN_GLPI_VERSION, 'max' => PLUGIN_USERNOTIFICATIONS_MAX_GLPI_VERSION]],
    ];
}

function plugin_usernotifications_check_prerequisites(): bool
{
    return version_compare(GLPI_VERSION, PLUGIN_USERNOTIFICATIONS_MIN_GLPI_VERSION, '>=')
        && version_compare(GLPI_VERSION, PLUGIN_USERNOTIFICATIONS_MAX_GLPI_VERSION, '<');
}
function plugin_usernotifications_check_config(bool $verbose = false): bool { return true; }