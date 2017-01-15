<?php

namespace Flosch\Bundle\ProxyBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;


/**
 * Configuration
 *
 * @package   Flosch\Bundle\ProxyBundle\DependencyInjection
 * @author    Florent Schildknecht
 *
 * @version   0.1
 * @since     2015-06
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('flosch_proxy');

        $rootNode
            ->children()
                ->scalarNode('proxy_base_url')
                    ->defaultValue('http://example.com/')
                    ->isRequired()
                ->end()
                ->scalarNode('users_provider_file_path')
                    ->defaultValue('%kernel.root_dir%/config/users.yml')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
