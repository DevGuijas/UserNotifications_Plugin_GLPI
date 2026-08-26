<?php

use Glpi\Plugin\HookManager;
use Glpi\Plugin\Hooks;
use GlpiPlugin\Usernotifications\Manager;

define('PLUGIN_USERNOTIFICATIONS_VERSION', '1.0.0');
define('PLUGIN_USERNOTIFICATIONS_MIN_GLPI_VERSION', '11.0.1');
define('PLUGIN_USERNOTIFICATIONS_MAX_GLPI_VERSION', '11.1.0');

function plugin_init_usernotifications(): void
{
    if (!Plugin::isPluginActive('usernotifications') || Session::getLoginUserID() <= 0) {
        return;
    }
    $hooks = new HookManager('usernotifications');
    $hooks->registerJavascriptFile('public/js/notification-bell.js');
    $hooks->registerCSSFile('public/css/notification-bell.css');
    $hooks->registerItemHook(Hooks::ITEM_ADD, Ticket_User::class, [Manager::class, 'onTicketUserAdded']);
    $hooks->registerItemHook(Hooks::ITEM_UPDATE, Ticket::class, [Manager::class, 'onTicketUpdated']);
    $hooks->registerItemHook(Hooks::ITEM_ADD, ITILFollowup::class, [Manager::class, 'onFollowupAdded']);
    $hooks->registerItemHook(Hooks::ITEM_ADD, TicketTask::class, [Manager::class, 'onTaskAdded']);
    $hooks->registerItemHook(Hooks::ITEM_ADD, TicketValidation::class, [Manager::class, 'onValidationAdded']);
    $hooks->registerItemHook(Hooks::ITEM_UPDATE, TicketValidation::class, [Manager::class, 'onValidationUpdated']);
}

function plugin_version_usernotifications(): array
{
    return [
        'name' => 'Sino de notificações',
        'version' => PLUGIN_USERNOTIFICATIONS_VERSION,
        'author' => 'SCRB',
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