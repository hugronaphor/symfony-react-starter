<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\ProjectSnapshotGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * ProjectSnapshotCommand.
 *
 * This command generates a comprehensive snapshot of this project's source files.
 *
 * **Usage:**
 * php bin/console app:project-snapshot
 * php bin/console app:project-snapshot --format text
 * php bin/console app:project-snapshot --output custom-snapshot.md
 */
#[AsCommand(
    name: 'app:project-snapshot',
    description: 'Generate a snapshot of all project source files',
    hidden: false
)]
class ProjectSnapshotCommand extends Command
{
    public function __construct(
        private readonly ProjectSnapshotGenerator $snapshotGenerator,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Generate a snapshot of all project source files')
            ->setHelp('Creates PROJECT_SNAPSHOT.txt with all source files.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $io->section('Generating Project Snapshot');
            $io->writeln('Processing project files...');

            // Generate snapshot
            $content = $this->snapshotGenerator->generate();

            // Write to file
            $outputPath = $this->projectDir.'/PROJECT_SNAPSHOT.txt';

            if (false === file_put_contents($outputPath, $content)) {
                $io->error('Failed to write snapshot file');

                return Command::FAILURE;
            }

            $fileSize = filesize($outputPath);
            $io->success('Snapshot generated successfully!');
            $io->table(
                ['Property', 'Value'],
                [
                    ['Output File', 'PROJECT_SNAPSHOT.txt'],
                    ['File Size', $this->formatBytes($fileSize)],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error generating snapshot: '.$e->getMessage());
            if ($output->isVerbose()) {
                $io->writeln($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }
}
