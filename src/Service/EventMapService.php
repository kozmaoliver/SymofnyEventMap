<?php

declare(strict_types=1);

namespace Kozmaoliver\SymfonyEventMap\Service;

class EventMapService
{
    private EventScanner $eventScanner;
    private ListenerMapper $listenerMapper;

    public function __construct(EventScanner $eventScanner, ListenerMapper $listenerMapper)
    {
        $this->eventScanner = $eventScanner;
        $this->listenerMapper = $listenerMapper;
    }

    public function generateEventMap(): array
    {
        $scannedEvents = $this->eventScanner->scanEvents();
        $registeredListeners = $this->listenerMapper->getAllEventListeners();

        $eventMap = [];

        // Process scanned events
        foreach ($scannedEvents as $file => $fileEvents) {
            foreach ($fileEvents['dispatches'] as $dispatch) {
                $eventKey = $dispatch['name'] ?? $dispatch['class'] ?? 'unknown';

                if (!isset($eventMap[$eventKey])) {
                    $eventMap[$eventKey] = [
                        'name' => $eventKey,
                        'dispatches' => [],
                        'listeners' => [],
                        'classes' => [],
                    ];
                }

                $eventMap[$eventKey]['dispatches'][] = [
                    'file' => $file,
                    'line' => $dispatch['line'],
                    'type' => $dispatch['class'] ? 'class' : 'string',
                ];
            }

            foreach ($fileEvents['classes'] as $eventClass) {
                $className = $eventClass['name'];

                if (!isset($eventMap[$className])) {
                    $eventMap[$className] = [
                        'name' => $className,
                        'dispatches' => [],
                        'listeners' => [],
                        'classes' => [],
                    ];
                }

                $eventMap[$className]['classes'][] = [
                    'file' => $file,
                    'line' => $eventClass['line'],
                    'name' => $className,
                ];
            }
        }

        // Add registered listeners
        foreach ($registeredListeners as $eventName => $listeners) {
            if (!isset($eventMap[$eventName])) {
                $eventMap[$eventName] = [
                    'name' => $eventName,
                    'dispatches' => [],
                    'listeners' => [],
                    'classes' => [],
                ];
            }

            $eventMap[$eventName]['listeners'] = $listeners;
        }

        return $eventMap;
    }

    public function exportToJson(): string
    {
        $eventMap = $this->generateEventMap();
        return json_encode($eventMap, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
