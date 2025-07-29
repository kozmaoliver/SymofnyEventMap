<?php

declare(strict_types=1);

namespace Kozmaoliver\SymfonyEventMap\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('event_map');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
            ->arrayNode('scan_directories')
            ->defaultValue(['src'])
            ->scalarPrototype()->end()
            ->end()
            ->arrayNode('exclude_patterns')
            ->defaultValue(['/vendor/', '/tests/', '/var/'])
            ->scalarPrototype()->end()
            ->end()
            ->end();

        return $treeBuilder;
    }
}

