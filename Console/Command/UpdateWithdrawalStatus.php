<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zwernemann\Withdrawal\Model\StatusUpdater;
use Zwernemann\Withdrawal\Model\WithdrawalRepository;

/**
 * CLI command to update withdrawal statuses based on credit memos
 */
class UpdateWithdrawalStatus extends Command
{
    private const OPTION_ORDER_ID = 'order-id';
    private const OPTION_ALL = 'all';

    public function __construct(
        protected readonly WithdrawalRepository $withdrawalRepository,
        protected readonly StatusUpdater        $statusUpdater,
        string                                  $name = null
    )
    {
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('withdrawal:status:update')
            ->setDescription('Update withdrawal status based on credit memos')
            ->addOption(
                self::OPTION_ORDER_ID,
                'o',
                InputOption::VALUE_OPTIONAL,
                'Order ID to check'
            )
            ->addOption(
                self::OPTION_ALL,
                'a',
                InputOption::VALUE_NONE,
                'Check all pending withdrawals'
            );

        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $orderId = $input->getOption(self::OPTION_ORDER_ID);
        $checkAll = $input->getOption(self::OPTION_ALL);

        if ($orderId) {
            return $this->updateSingleOrder((int)$orderId, $output);
        }

        if ($checkAll) {
            return $this->updateAllPending($output);
        }

        $output->writeln('<error>Please specify either --order-id=<ID> or --all</error>');
        return Command::FAILURE;
    }

    /**
     * Update status for a single order
     *
     * @param int $orderId
     * @param OutputInterface $output
     * @return int
     */
    protected function updateSingleOrder(int $orderId, OutputInterface $output): int
    {
        $output->writeln("<info>Checking withdrawal for Order ID: {$orderId}</info>");

        $withdrawal = $this->withdrawalRepository->getByOrderId($orderId);
        if (!$withdrawal || !$withdrawal->getId()) {
            $output->writeln('<error>No withdrawal found for this order.</error>');
            return Command::FAILURE;
        }

        $currentStatus = $withdrawal->getData('status');
        $output->writeln("Current status: {$currentStatus}");

        if ($currentStatus !== 'pending') {
            $output->writeln('<comment>Status is not pending. No action needed.</comment>');
            return Command::SUCCESS;
        }

        $updated = $this->statusUpdater->updateStatusIfFullyCredited($orderId);

        if ($updated) {
            $output->writeln('<info>✓ Status updated to: confirmed</info>');
            return Command::SUCCESS;
        }

        $output->writeln('<comment>Not all withdrawn items are credited yet.</comment>');
        return Command::SUCCESS;
    }

    /**
     * Update status for all pending withdrawals
     *
     * @param OutputInterface $output
     * @return int
     */
    protected function updateAllPending(OutputInterface $output): int
    {
        $output->writeln('<info>Checking all pending withdrawals...</info>');

        $withdrawals = $this->withdrawalRepository->getList();
        $pendingCount = 0;
        $updatedCount = 0;

        foreach ($withdrawals as $withdrawalData) {
            if ($withdrawalData['status'] !== 'pending') {
                continue;
            }

            $pendingCount++;
            $orderId = (int)$withdrawalData['order_id'];

            $output->writeln("Checking Order ID: {$orderId}");

            $updated = $this->statusUpdater->updateStatusIfFullyCredited($orderId);
            if ($updated) {
                $updatedCount++;
                $output->writeln("<info>  ✓ Updated to confirmed</info>");
            }
        }

        $output->writeln('');
        $output->writeln("<info>Summary:</info>");
        $output->writeln("  Total pending: {$pendingCount}");
        $output->writeln("  Updated: {$updatedCount}");

        return Command::SUCCESS;
    }
}

