<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Command;

use Kit\DigitalPageFlip\Service\FlipbookCleanupService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class CleanupCommand extends Command
{
    public function __construct(
        private readonly FlipbookCleanupService $cleanupService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Find and remove orphaned FAL files, references, and folders from deleted flipbooks.')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Preview what would be cleaned without making changes',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        if ($dryRun) {
            $io->note('Dry-run mode: no changes will be made.');
        }

        $result = $this->cleanupService->collectOrphans($dryRun);

        $total = $result['references'] + $result['files'] + $result['folders'];

        if ($total === 0) {
            $io->success('No orphaned data found.');
            return Command::SUCCESS;
        }

        $action = $dryRun ? 'Found' : 'Deleted';

        if ($result['references'] > 0) {
            $io->writeln(sprintf('  %s %d orphaned sys_file_reference record(s).', $action, $result['references']));
        }
        if ($result['files'] > 0) {
            $io->writeln(sprintf('  %s %d orphaned FAL file(s).', $action, $result['files']));
        }
        if ($result['folders'] > 0) {
            $io->writeln(sprintf('  %s %d orphaned folder(s).', $action, $result['folders']));
        }

        if ($dryRun) {
            $io->warning(sprintf('Dry-run: %d item(s) would be cleaned up. Run without --dry-run to proceed.', $total));
        } else {
            $io->success(sprintf('Cleanup completed. %d item(s) removed.', $total));
        }

        return Command::SUCCESS;
    }
}
