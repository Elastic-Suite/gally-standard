<?php
/**
 * DISCLAIMER.
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);
/**
 * Allows to define Bundle configuration structure, see https://symfony.com/doc/current/components/config/definition.html.
 */

namespace Gally\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @codeCoverageIgnore
 */
class Configuration implements ConfigurationInterface
{
    public const ROOT_NODE_CONFIG = 'gally';

    /**
     * Example get from https://symfony.com/doc/current/bundles/configuration.html#processing-the-configs-array
     * Concrete examples: vendor/api-platform/core/src/Bridge/Symfony/Bundle/DependencyInjection/Configuration.php.
     *
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE_CONFIG);

        $treeBuilder->getRootNode()
            ->children()
                // Menu config
                ->arrayNode('menu')
                    ->useAttributeAsKey('code')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('order')->end()
                            ->scalarNode('css_class')->end()
                            ->scalarNode('parent')->end()
                            ->scalarNode('path')->end()
                        ->end()
                    ->end()
                ->end()

                // Analysis config
                ->arrayNode('analysis')
                    ->useAttributeAsKey('language')
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode('char_filters')
                                ->useAttributeAsKey('name')
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('type')->isRequired()->end()
                                        ->variableNode('params')->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('filters')
                                ->useAttributeAsKey('name')
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('type')->isRequired()->end()
                                        ->variableNode('params')->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('analyzers')
                                ->useAttributeAsKey('name')
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('type')->defaultValue('custom')->end()
                                        ->arrayNode('char_filter')
                                            ->isRequired()
                                            ->scalarPrototype()->end()
                                        ->end()
                                        ->scalarNode('tokenizer')->isRequired()->end()
                                        ->arrayNode('filter')
                                            ->isRequired()
                                            ->scalarPrototype()->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('normalizers')
                                ->useAttributeAsKey('name')
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('type')->defaultValue('custom')->end()
                                        ->arrayNode('char_filter')
                                            ->scalarPrototype()->end()
                                        ->end()
                                        ->arrayNode('filter')
                                            ->isRequired()
                                            ->scalarPrototype()->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()

                // Indices setting config
                ->arrayNode('indices_settings')
                    ->children()
                        ->scalarNode('prefix')
                            ->isRequired()
                        ->end()
                        ->scalarNode('timestamp_pattern')
                            ->isRequired()
                        ->end()
                        ->integerNode('number_of_shards')
                            ->isRequired()
                            ->min(1)
                        ->end()
                        ->integerNode('number_of_replicas')
                            ->isRequired()
                            ->min(0)
                        ->end()
                        ->integerNode('batch_indexing_size')
                            ->isRequired()
                            ->min(1)
                        ->end()
                        ->integerNode('time_before_ghost')
                            ->isRequired()
                            ->min(1)
                        ->end()
                    ->end()
                ->end()

                // Rename graphQL query
                ->arrayNode('graphql_query_renaming')
                    ->useAttributeAsKey('ressource_class')
                    ->arrayPrototype()
                        ->children()
                            ->variableNode('renamings')->end()
                        ->end()
                    ->end()
                ->end()

                // Indices setting config
                ->arrayNode('search_settings')
                    ->children()
                        ->scalarNode('default_date_field_format')->end()
                        ->scalarNode('default_distance_unit')->end()
                        ->arrayNode('aggregations')
                            ->children()
                                ->booleanNode('coverage_use_indexed_fields_property')
                                    ->defaultFalse()
                                ->end()
                                ->scalarNode('default_date_range_interval')->end()
                                ->arrayNode('default_distance_ranges')
                                    ->arrayPrototype()
                                        ->children()
                                            ->scalarNode('from')->end()
                                            ->scalarNode('to')->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('sort')
                            ->children()
                                ->arrayNode('default_asc_sort_field')
                                    ->scalarPrototype()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()

                // Autocomplete settings
                ->arrayNode('autocomplete_settings')
                    ->children()
                        ->arrayNode('product_attribute')
                            ->children()
                                ->integerNode('max_size')
                                    ->min(0)
                                    ->defaultValue(3)
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('document_attribute')
                            ->children()
                                ->integerNode('max_size')
                                    ->min(0)
                                    ->defaultValue(3)
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()

                // Indices setting config
                ->arrayNode('relevance')
                    ->useAttributeAsKey('localized_catalog_code')
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode('request_types')
                                ->useAttributeAsKey('request_type')
                                ->arrayPrototype()
                                    ->children()
                                        ->arrayNode('fulltext')
                                            ->children()
                                                ->scalarNode('minimumShouldMatch')->end()
                                                ->floatNode('tieBreaker')->end()
                                            ->end()
                                        ->end()
                                        ->arrayNode('phraseMatch')
                                            ->children()
                                                ->booleanNode('enabled')->end()
                                                ->integerNode('boost')->end()
                                            ->end()
                                        ->end()
                                        ->arrayNode('cutOffFrequency')
                                            ->children()
                                                ->floatNode('value')->end()
                                            ->end()
                                        ->end()
                                        ->arrayNode('fuzziness')
                                            ->children()
                                                ->booleanNode('enabled')->end()
                                                ->scalarNode('value')->end()
                                                ->integerNode('prefixLength')->end()
                                                ->integerNode('maxExpansions')->end()
                                            ->end()
                                        ->end()
                                        ->arrayNode('phonetic')
                                            ->children()
                                                ->booleanNode('enabled')->end()
                                            ->end()
                                        ->end()
                                        ->arrayNode('span')
                                            ->children()
                                                ->scalarNode('boost')->end()
                                                ->integerNode('slop')->end()
                                                ->booleanNode('in_order')->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()

                // Base urls config
                ->arrayNode('base_url')
                    ->children()
                        ->scalarNode('media')
                            ->defaultValue('https://%env(SERVER_NAME)%/media/catalog/product/')
                        ->end()
                    ->end()
                ->end()

                // Request context config
                ->arrayNode('request_context')
                    ->children()
                        ->arrayNode('headers')
                            ->scalarPrototype()->end()
                        ->end()
                    ->end()
                ->end()

                // Request types config
                ->arrayNode('request_types')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('code')
                                ->isRequired()
                            ->end()
                            ->scalarNode('label')
                                ->isRequired()
                            ->end()
                            ->scalarNode('limitation_type')
                                ->isRequired()
                            ->end()
                        ->end()
                    ->end()
                ->end()

                // Default price group id config
                ->scalarNode('default_price_group_id')
                    ->defaultValue('0')
                ->end()

                // Default reference location
                ->scalarNode('default_reference_location')
                    ->defaultValue('48.981299, 2.309959')
                ->end()

                // Ingest pipeline configuration
                ->scalarNode('pipeline_prefix')->isRequired()->end()

            ->end();

        return $treeBuilder;
    }
}
