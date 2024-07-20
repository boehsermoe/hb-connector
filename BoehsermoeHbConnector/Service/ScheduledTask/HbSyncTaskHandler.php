<?php
namespace Boehsermoe\HbConnector\Service\ScheduledTask;

use Boehsermoe\HbConnector\Service\HbApiService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: HbSyncTask::class)]
class HbSyncTaskHandler extends ScheduledTaskHandler
{
    public function __construct(EntityRepository $scheduledTaskRepository, private readonly HbApiService $hbApiService, ?LoggerInterface $exceptionLogger = null)
    {
        parent::__construct($scheduledTaskRepository, $exceptionLogger);
    }

    public function run(): void
    {
        $results = $this->hbApiService->checkLawTexts();
    }
}