<?php

declare(strict_types=1);

namespace Kozmaoliver\SymfonyEventMap;

use Kozmaoliver\SymfonyEventMap\DependencyInjection\EventMapExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class EventMapBundle extends AbstractBundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new EventMapExtension();
    }
}

