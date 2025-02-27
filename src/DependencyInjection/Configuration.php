<?php

declare(strict_types=1);

namespace PrintPlius\SyliusApolloIntegrationPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('printplius_sylius_skeleton_plugin');
        $rootNode = $treeBuilder->getRootNode();

        return $treeBuilder;
    }
}
