<?php

namespace App\Command;

use App\Services\Configuration\Configuration;
use App\Services\FileManager\File;
use App\Services\FileManager\FileExplorer;
use App\Services\Search\SearchEngine;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'search:index',
    description: 'Re-emit every docs.',
)]
class SearchIndexCommand extends Command
{
    public function __construct(
        private FileExplorer $fileExplorer,
        private SearchEngine $engine,

    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fileList = $this->fileExplorer->getFullSiteContent();

        $io = new SymfonyStyle($input, $output);

        foreach ($fileList as $item) {
            $this->indexFile($item);
        }

        $io->success('Reindex successfuly sent');

        return Command::SUCCESS;
    }

    private function indexFile(File $fileItem)
    {
        $this->engine->index($fileItem->path, $fileItem->filename, $fileItem->content, $fileItem->metadata);

    }
}
