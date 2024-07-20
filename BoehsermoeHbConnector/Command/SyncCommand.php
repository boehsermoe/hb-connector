<?php

declare(strict_types=1);

namespace Boehsermoe\HbConnector\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Boehsermoe\HbConnector\Service\HbApiService;

#[AsCommand('hblawtext:sync', 'Sync HB Rechtstexte')]
class SyncCommand extends Command
{
    public function __construct(private readonly HbApiService $hbApiService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $context = Context::createDefaultContext();

        $io = new SymfonyStyle($input, $output);

        $this->hbApiService->checkLawTexts();

        return self::SUCCESS;
    }
}
