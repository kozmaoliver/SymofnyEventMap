<?php
declare(strict_types=1);

namespace Kozmaoliver\SymfonyEventMap\Command;

use Kozmaoliver\EventMap\Service\EventMapService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'event-map:generate',
    description: 'Generate an event map showing all events and their listeners'
)]
class EventMapCommand extends Command
{
    private EventMapService $eventMapService;

    public function __construct(EventMapService $eventMapService)
    {
        parent::__construct();
        $this->eventMapService = $eventMapService;
    }

    protected function configure(): void
    {
        $this
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Output file path', 'event-map.json')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format (json|table)', 'json');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Event Map Generator');

        $eventMap = $this->eventMapService->generateEventMap();
        $format = $input->getOption('format');
        $outputFile = $input->getOption('output');

        if ($format === 'table') {
            $this->displayAsTable($io, $eventMap);
        } else {
            $json = $this->eventMapService->exportToJson();
            file_put_contents($outputFile, $json);
            $io->success("Event map generated and saved to: $outputFile");
        }

        $io->info(sprintf('Found %d events', count($eventMap)));

        return Command::SUCCESS;
    }

    private function displayAsTable(SymfonyStyle $io, array $eventMap): void
    {
        $rows = [];

        foreach ($eventMap as $eventName => $eventData) {
            $rows[] = [
                $eventName,
                count($eventData['dispatches']),
                count($eventData['listeners']),
                count($eventData['classes']),
                implode(', ', array_column($eventData['listeners'], 'class')),
            ];
        }

        $io->table(
            ['Event Name', 'Dispatches', 'Listeners', 'Classes', 'Listener Classes'],
            $rows
        );
    }
}

