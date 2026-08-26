<?php

namespace GlpiPlugin\Usernotifications;

use CommonITILActor;
use CommonITILValidation;
use Group;
use Group_User;
use ITILFollowup;
use Ticket;
use TicketTask;
use Ticket_User;
use TicketValidation;
use User;

final class Manager
{
    private const RETENTION_DAYS = 30;

    public static function getTable(): string { return 'glpi_plugin_usernotifications_notifications'; }

    public static function install(): bool
    {
        global $DB;
        $table = self::getTable();
        if (!$DB->tableExists($table)) {
            $charset = \DBConnection::getDefaultCharset();
            $collation = \DBConnection::getDefaultCollation();
            $DB->doQuery("CREATE TABLE `$table` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `users_id` INT UNSIGNED NOT NULL,
                `ticket_id` INT UNSIGNED NOT NULL DEFAULT '0',
                `kind` VARCHAR(32) NOT NULL,
                `message` VARCHAR(1024) NOT NULL,
                `source_key` VARCHAR(128) NOT NULL,
                `is_read` TINYINT(1) NOT NULL DEFAULT '0',
                `date_creation` DATETIME NOT NULL,
                `date_read` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `recipient_source` (`users_id`, `source_key`),
                KEY `user_read_date` (`users_id`, `is_read`, `date_creation`),
                KEY `retention` (`date_creation`)
            ) ENGINE=InnoDB DEFAULT CHARSET=$charset COLLATE=$collation ROW_FORMAT=DYNAMIC");
        }
        return true;
    }

    public static function uninstall(): bool
    {
        global $DB;
        if ($DB->tableExists(self::getTable())) { $DB->doQuery('DROP TABLE `' . self::getTable() . '`'); }
        return true;
    }

    public static function onTicketUserAdded(Ticket_User $link): void
    {
        if ((int) ($link->fields['type'] ?? 0) !== CommonITILActor::ASSIGN) { return; }
        $ticketId = (int) ($link->fields['tickets_id'] ?? 0);
        $recipientId = (int) ($link->fields['users_id'] ?? 0);
        if ($ticketId > 0 && $recipientId > 0) {
            self::add($recipientId, $ticketId, 'assignment', sprintf(__('Você foi atribuído ao chamado #%d.', 'usernotifications'), $ticketId), 'assignment:' . $link->getID());
        }
    }

    public static function onTicketUpdated(Ticket $ticket): void
    {
        if (($ticket->input['_from_assignment'] ?? false) === true) { return; }
        $ticketId = (int) $ticket->getID();
        if ($ticketId > 0) {
            self::notifyAssignedUsers($ticketId, (int) ($ticket->fields['users_id_lastupdater'] ?? 0), 'ticket', sprintf(__('O chamado #%d foi atualizado.', 'usernotifications'), $ticketId), 'ticketupdate:' . $ticketId . ':' . ($ticket->fields['date_mod'] ?? date('Y-m-d H:i:s')));
        }
    }
    public static function onFollowupAdded(ITILFollowup $followup): void
    {
        if (($followup->fields['itemtype'] ?? '') !== Ticket::class && ($followup->fields['itemtype'] ?? '') !== 'Ticket') { return; }
        $ticketId = (int) ($followup->fields['items_id'] ?? 0);
        if ($ticketId > 0) {
            self::notifyAssignedUsers($ticketId, (int) ($followup->fields['users_id'] ?? 0), 'followup', sprintf(__('Há uma atualização no chamado #%d.', 'usernotifications'), $ticketId), 'followup:' . $followup->getID());
        }
    }

    public static function onTaskAdded(TicketTask $task): void
    {
        $ticketId = (int) ($task->fields['tickets_id'] ?? 0);
        if ($ticketId > 0) {
            self::notifyAssignedUsers($ticketId, (int) ($task->fields['users_id'] ?? 0), 'task', sprintf(__('Há uma nova tarefa no chamado #%d.', 'usernotifications'), $ticketId), 'task:' . $task->getID());
        }
    }

    public static function onValidationAdded(TicketValidation $validation): void { self::notifyValidationTarget($validation); }
    public static function onValidationUpdated(TicketValidation $validation): void
    {
        if ((int) ($validation->fields['status'] ?? 0) === CommonITILValidation::WAITING) { self::notifyValidationTarget($validation); }
    }

    /** @return array<int, array<string, mixed>> */
    public static function getForUser(int $userId): array
    {
        global $DB;
        self::purgeExpired();
        self::importPendingApprovals($userId);
        $notifications = [];
        foreach ($DB->request(['FROM' => self::getTable(), 'WHERE' => ['users_id' => $userId], 'ORDER' => ['is_read ASC', 'date_creation DESC'], 'LIMIT' => 100]) as $row) {
            $notifications[] = ['id' => (int) $row['id'], 'ticket_id' => (int) $row['ticket_id'], 'kind' => (string) $row['kind'], 'message' => (string) $row['message'], 'is_read' => (bool) $row['is_read'], 'date_creation' => (string) $row['date_creation'], 'url' => '/front/ticket.form.php?id=' . (int) $row['ticket_id']];
        }
        return $notifications;
    }

    public static function markAllAsRead(int $userId): void
    {
        global $DB;
        $DB->update(self::getTable(), ['is_read' => 1, 'date_read' => date('Y-m-d H:i:s')], ['users_id' => $userId, 'is_read' => 0]);
    }

    public static function markAsRead(int $userId, int $notificationId): void
    {
        global $DB;
        $DB->update(self::getTable(), ['is_read' => 1, 'date_read' => date('Y-m-d H:i:s')], [
            'id' => $notificationId,
            'users_id' => $userId,
            'is_read' => 0,
        ]);
    }

    private static function notifyAssignedUsers(int $ticketId, int $authorId, string $kind, string $message, string $sourceKey): void
    {
        $ticket = new Ticket();
        if (!$ticket->getFromDB($ticketId)) { return; }
        foreach ($ticket->getAllUsers(CommonITILActor::ASSIGN) as $recipientId) {
            $recipientId = (int) $recipientId;
            if ($recipientId > 0 && $recipientId !== $authorId) { self::add($recipientId, $ticketId, $kind, $message, $sourceKey); }
        }
    }

    private static function notifyValidationTarget(TicketValidation $validation): void
    {
        if ((int) ($validation->fields['status'] ?? 0) !== CommonITILValidation::WAITING) { return; }
        $ticketId = (int) ($validation->fields['tickets_id'] ?? 0);
        if ($ticketId <= 0) { return; }
        $requester = (int) ($validation->fields['users_id'] ?? 0);
        $name = $requester > 0 ? getUserName($requester) : __('Um usuário', 'usernotifications');
        $message = sprintf(__('%s pediu a sua aprovação no chamado #%d.', 'usernotifications'), $name, $ticketId);
        foreach (self::getValidationRecipients($validation) as $recipientId) { self::add($recipientId, $ticketId, 'approval', $message, 'validation:' . $validation->getID() . ':' . $recipientId); }
    }

    /** @return list<int> */
    private static function getValidationRecipients(TicketValidation $validation): array
    {
        $type = (string) ($validation->fields['itemtype_target'] ?? '');
        $targetId = (int) ($validation->fields['items_id_target'] ?? 0);
        if ($targetId <= 0) { return []; }
        if ($type === User::class || $type === 'User') { return [$targetId]; }
        if ($type === Group::class || $type === 'Group') { return array_map(static fn(array $user): int => (int) $user['id'], Group_User::getGroupUsers($targetId)); }
        return [];
    }

    private static function importPendingApprovals(int $userId): void
    {
        global $DB;
        foreach ($DB->request(['FROM' => TicketValidation::getTable(), 'WHERE' => array_merge(['status' => CommonITILValidation::WAITING], TicketValidation::getTargetCriteriaForUser($userId))]) as $row) {
            $validation = new TicketValidation();
            if ($validation->getFromDB((int) $row['id'])) { self::notifyValidationTarget($validation); }
        }
    }

    private static function add(int $userId, int $ticketId, string $kind, string $message, string $sourceKey): void
    {
        global $DB;
        if ($userId <= 0 || self::exists($userId, $sourceKey)) { return; }
        $DB->insert(self::getTable(), ['users_id' => $userId, 'ticket_id' => $ticketId, 'kind' => $kind, 'message' => $message, 'source_key' => $sourceKey, 'date_creation' => date('Y-m-d H:i:s')]);
    }

    private static function exists(int $userId, string $sourceKey): bool
    {
        global $DB;
        foreach ($DB->request(['FROM' => self::getTable(), 'WHERE' => ['users_id' => $userId, 'source_key' => $sourceKey], 'LIMIT' => 1]) as $_row) { return true; }
        return false;
    }

    private static function purgeExpired(): void
    {
        global $DB;
        $DB->delete(self::getTable(), ['date_creation' => ['<', date('Y-m-d H:i:s', strtotime('-' . self::RETENTION_DAYS . ' days'))]]);
    }
}