<?php

declare(strict_types=1);

namespace Kozmaoliver\EventMapBundle;

use Kozmaoliver\EventMapBundle\DependencyInjection\EventMapExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class EventMapBundle extends AbstractBundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new EventMapExtension();
    }
}

