<?php
namespace Boehsermoe\HbConnector\Service\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class HbSyncTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'boehsermoe.hb_sync_task';
    }

    public static function getDefaultInterval(): int
    {
        return 3600; // 60 minutes
    }
}