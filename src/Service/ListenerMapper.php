<?php

declare(strict_types=1);

namespace Kozmaoliver\SymfonyEventMap\Service;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ListenerMapper
{
    private EventDispatcherInterface $eventDispatcher;
    private ContainerInterface $container;

    public function __construct(EventDispatcherInterface $eventDispatcher, ContainerInterface $container)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->container = $container;
    }

    public function getListenersForEvent(string $eventName): array
    {
        $listeners = [];

        foreach ($this->eventDispatcher->getListeners($eventName) as $listener) {
            $listenerInfo = $this->analyzeListener($listener);
            if ($listenerInfo) {
                $listeners[] = $listenerInfo;
            }
        }

        return $listeners;
    }

    public function getAllEventListeners(): array
    {
        $allListeners = [];

        // Get all registered event listeners
        $listenersByEvent = $this->getRegisteredListeners();

        foreach ($listenersByEvent as $eventName => $listeners) {
            $allListeners[$eventName] = [];

            foreach ($listeners as $listener) {
                $listenerInfo = $this->analyzeListener($listener);
                if ($listenerInfo) {
                    $allListeners[$eventName][] = $listenerInfo;
                }
            }
        }

        return $allListeners;
    }

    private function getRegisteredListeners(): array
    {
        $listeners = [];

        // Use reflection to get all registered listeners
        $reflection = new \ReflectionClass($this->eventDispatcher);
        $property = $reflection->getProperty('listeners');
        $property->setAccessible(true);

        return $property->getValue($this->eventDispatcher) ?? [];
    }

    private function analyzeListener($listener): ?array
    {
        if (is_array($listener) && count($listener) === 2) {
            [$object, $method] = $listener;

            return [
                'class' => get_class($object),
                'method' => $method,
                'type' => 'method',
                'service_id' => $this->findServiceId($object),
            ];
        }

        if (is_callable($listener)) {
            if (is_string($listener)) {
                return [
                    'class' => null,
                    'method' => $listener,
                    'type' => 'function',
                    'service_id' => null,
                ];
            }

            if ($listener instanceof \Closure) {
                return [
                    'class' => 'Closure',
                    'method' => null,
                    'type' => 'closure',
                    'service_id' => null,
                ];
            }
        }

        return null;
    }

    private function findServiceId($object): ?string
    {
        $className = get_class($object);

        // Try to find the service ID for this class
        foreach ($this->container->getServiceIds() as $serviceId) {
            try {
                if ($this->container->has($serviceId)) {
                    $service = $this->container->get($serviceId);
                    if ($service === $object) {
                        return $serviceId;
                    }
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return $className;
    }
}

