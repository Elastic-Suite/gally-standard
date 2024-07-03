<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @package   Gally
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\GraphQl\Decoration\Resolver\Stage;

use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Util\ArrayTrait;
use ApiPlatform\Metadata\Operation;

/**
 * Some gally args do not fit with the way where API Platform manage graphql args.
 * For example this kind of args is authorized by graphql but not by "API Platform":
 * {
 *   documents(
 *     filter: [{matchFilter: {field:"content", match:"article"}}, {matchFilter: {field:"content_heading", match:"article"}}]
 *   )
 *   {...}
 * }
 * It will be transformed in one filter on the context operation, $context[...]['filters'] = ['matchFilter' => ['field' => 'content_heading', match: 'article' ]] instead of
 * $context[...]['filters'] = [['matchFilter' => ['field' => 'content', match: 'article' ]], ['matchFilter' => ['field' => 'content_heading', match: 'article' ]]]
 *
 * As we need this syntax for some args in our GraphQL queries, we added a mechanism to split args in two categories:
 * - API Platform args: They are managed natively by API Platform, nothing changes (they stay in the array key 'args').
 * - Gally args: They are moved to the array key 'gally_args', and are treated by the gally code.
 *
 * To make an 'arg' as an "Gally arg", you have to set is_gally_arg to true in the ApiResource attribute, @see \Gally\Search\Model\Document.
 */
class ReadStage implements ProviderInterface
{
    use ArrayTrait;

    public const IS_GRAPHQL_GALLY_ARG_KEY = 'is_gally_arg';

    public const GRAPHQL_GALLY_ARGS_KEY = 'gally_args';

    public function __construct(
        private ProviderInterface $decorated,
    ) {
    }

    /**
     * @inheritdoc
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        /**
         * Move gally args in a dedicated array key.
         */
        $args = $operation->getArgs() ?? [];
        foreach ($args as $argName => $arg) {
            if (null !== ($arg[self::IS_GRAPHQL_GALLY_ARG_KEY] ?? null) && isset($context['args'][$argName])) {
                $context[self::GRAPHQL_GALLY_ARGS_KEY][$argName] = $context['args'][$argName];
                unset($context['args'][$argName]);
            }
        }

        return $this->decorated->provide($operation, $uriVariables, $context);
    }
}
