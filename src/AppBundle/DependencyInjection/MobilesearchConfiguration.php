<?php

namespace AppBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration.
 */
class MobilesearchConfiguration implements ConfigurationInterface
{
    /**
     * @var string
     */
    private $alias;

    /**
     * MobilesearchConfiguration constructor.
     *
     * @param string $alias
     *   Extension alias.
     */
    public function __construct($alias)
    {
        $this->alias = $alias;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $root = $treeBuilder->root($this->alias);

        $root
            ->children()
                ->booleanNode('image_full_url')
                    ->defaultFalse()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
