<?php

namespace MobileSearch\v2\RestBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('mobile_search_v2_rest');

        $rootNode->children()
            ->integerNode('items_limit')->defaultValue(10)->end()
            ->integerNode('items_offset')->defaultValue(0)->end()
        ->end();

        return $treeBuilder;
    }
}
