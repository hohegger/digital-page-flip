<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Command;

use Kit\DigitalPageFlip\Domain\Model\Flipbook;
use Kit\DigitalPageFlip\Domain\Repository\FlipbookRepository;
use Kit\DigitalPageFlip\Service\PdfConversionService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

final class ConvertPdfCommand extends Command
{
    public function __construct(
        private readonly FlipbookRepository $flipbookRepository,
        private readonly PdfConversionService $conversionService,
        private readonly PersistenceManager $persistenceManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Converts PDF files of pending flipbook records to page images.')
            ->addArgument(
                'uid',
                InputArgument::OPTIONAL,
                'UID of a specific flipbook to convert (converts all pending if omitted)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $uid = $input->getArgument('uid');

        if ($uid !== null) {
            $uid = (int) $uid;
            $flipbook = $this->flipbookRepository->findByUid($uid);
            if (!$flipbook instanceof Flipbook) {
                $io->error(sprintf('Flipbook with UID %d not found.', $uid));
                return Command::FAILURE;
            }
            return $this->convertSingle($flipbook, $io);
        }

        return $this->convertAllPending($io);
    }

    private function convertSingle(Flipbook $flipbook, SymfonyStyle $io): int
    {
        $io->info(sprintf('Converting flipbook "%s" (UID: %d)...', $flipbook->getTitle(), $flipbook->getUid()));

        try {
            $this->conversionService->convert($flipbook);
            $this->persistenceManager->persistAll();
            $io->success(sprintf('Conversion completed. %d pages generated.', $flipbook->getPageCount()));
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error(sprintf('Conversion failed: %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }

    private function convertAllPending(SymfonyStyle $io): int
    {
        $flipbooks = $this->flipbookRepository->findPending();
        $count = $flipbooks->count();

        if ($count === 0) {
            $io->success('No pending flipbooks found.');
            return Command::SUCCESS;
        }

        $io->info(sprintf('Found %d pending flipbook(s).', $count));
        $failures = 0;

        foreach ($flipbooks as $flipbook) {
            $result = $this->convertSingle($flipbook, $io);
            if ($result !== Command::SUCCESS) {
                $failures++;
            }
        }

        if ($failures > 0) {
            $io->warning(sprintf('%d of %d conversions failed.', $failures, $count));
            return Command::FAILURE;
        }

        $io->success(sprintf('All %d flipbook(s) converted successfully.', $count));
        return Command::SUCCESS;
    }
}
