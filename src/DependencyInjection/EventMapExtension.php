<?php
declare(strict_types=1);

namespace Kozmaoliver\SymfonyEventMap\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class EventMapExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration(false);
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('event_map.scan_directories', $config['scan_directories']);
        $container->setParameter('event_map.exclude_patterns', $config['exclude_patterns']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
    }
}
