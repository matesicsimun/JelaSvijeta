<?php

namespace App\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class DataGenerationConfiguration implements ConfigurationInterface
{

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('data_generation_configuration');
        $rootNode = $treeBuilder->getRootNode();

        $this->addDataGenerationConfig($rootNode);

        return $treeBuilder;
    }

    private function addDataGenerationConfig(ArrayNodeDefinition $rootNode): void
    {
        $rootNode->children()->arrayNode('languages')->end();
    }
}