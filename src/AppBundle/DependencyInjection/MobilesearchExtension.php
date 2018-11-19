<?php

namespace AppBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Class MobilesearchExtension.
 */
class MobilesearchExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );

        $loader->load('services.yml');

        $mosConfig = new MobilesearchConfiguration($this->getAlias());
        $config = $this->processConfiguration($mosConfig, $configs);

        $container->setParameter('mobilesearch.image_full_url', $config['image_full_url']);
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return 'mobilesearch';
    }
}
