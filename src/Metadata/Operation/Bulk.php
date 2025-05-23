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

namespace Gally\Metadata\Operation;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\OpenApi\Attributes\Webhook;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\State\OptionsInterface;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class Bulk extends HttpOperation implements CollectionOperationInterface
{
    public function __construct(
        ?string $uriTemplate = null,
        ?array $types = null,
        $formats = null,
        $inputFormats = null,
        $outputFormats = null,
        $uriVariables = null,
        ?string $routePrefix = null,
        ?string $routeName = null,
        ?array $defaults = null,
        ?array $requirements = null,
        ?array $options = null,
        ?bool $stateless = null,
        ?string $sunset = null,
        ?string $acceptPatch = null,
        $status = null,
        ?string $host = null,
        ?array $schemes = null,
        ?string $condition = null,
        ?string $controller = null,
        ?array $headers = null,
        ?array $cacheHeaders = null,
        ?array $paginationViaCursor = null,
        ?array $hydraContext = null,
        ?array $openapiContext = null,
        bool|OpenApiOperation|Webhook|null $openapi = null,
        ?array $exceptionToStatus = null,
        ?bool $queryParameterValidationEnabled = null,
        ?array $links = null,

        ?string $shortName = null,
        ?string $class = null,
        ?bool $paginationEnabled = null,
        ?string $paginationType = null,
        ?int $paginationItemsPerPage = null,
        ?int $paginationMaximumItemsPerPage = null,
        ?bool $paginationPartial = null,
        ?bool $paginationClientEnabled = null,
        ?bool $paginationClientItemsPerPage = null,
        ?bool $paginationClientPartial = null,
        ?bool $paginationFetchJoinCollection = null,
        ?bool $paginationUseOutputWalkers = null,
        ?array $order = null,
        ?string $description = null,
        ?array $normalizationContext = null,
        ?array $denormalizationContext = null,
        ?bool $collectDenormalizationErrors = null,
        string|\Stringable|null $security = null,
        ?string $securityMessage = null,
        string|\Stringable|null $securityPostDenormalize = null,
        ?string $securityPostDenormalizeMessage = null,
        string|\Stringable|null $securityPostValidation = null,
        ?string $securityPostValidationMessage = null,
        ?string $deprecationReason = null,
        ?array $filters = null,
        ?array $validationContext = null,
        $input = null,
        $output = null,
        $mercure = null,
        $messenger = null,
        ?bool $elasticsearch = null,
        ?int $urlGenerationStrategy = null,
        ?bool $read = null,
        ?bool $deserialize = null,
        ?bool $validate = null,
        ?bool $write = null,
        ?bool $serialize = null,
        ?bool $fetchPartial = null,
        ?bool $forceEager = null,
        ?int $priority = null,
        ?string $name = null,
        $provider = null,
        $processor = null,
        ?OptionsInterface $stateOptions = null,
        array|Parameters|null $parameters = null,
        array $extraProperties = [],
        private ?string $itemUriTemplate = null
    ) {
        parent::__construct(
            method: 'POST',
            uriTemplate: $uriTemplate,
            types: $types,
            formats: $formats,
            inputFormats: $inputFormats,
            outputFormats: $outputFormats,
            uriVariables: $uriVariables,
            routePrefix: $routePrefix,
            routeName: $routeName,
            defaults: $defaults,
            requirements: $requirements,
            options: $options,
            stateless: $stateless,
            sunset: $sunset,
            acceptPatch: $acceptPatch,
            status: $status,
            host: $host,
            schemes: $schemes,
            condition: $condition,
            controller: $controller,
            headers: $headers,
            cacheHeaders: $cacheHeaders,
            paginationViaCursor: $paginationViaCursor,
            hydraContext: $hydraContext,
            openapiContext: $openapiContext,
            openapi: $openapi,
            exceptionToStatus: $exceptionToStatus,
            queryParameterValidationEnabled: $queryParameterValidationEnabled,
            links: $links,
            shortName: $shortName,
            class: $class,
            paginationEnabled: $paginationEnabled,
            paginationType: $paginationType,
            paginationItemsPerPage: $paginationItemsPerPage,
            paginationMaximumItemsPerPage: $paginationMaximumItemsPerPage,
            paginationPartial: $paginationPartial,
            paginationClientEnabled: $paginationClientEnabled,
            paginationClientItemsPerPage: $paginationClientItemsPerPage,
            paginationClientPartial: $paginationClientPartial,
            paginationFetchJoinCollection: $paginationFetchJoinCollection,
            paginationUseOutputWalkers: $paginationUseOutputWalkers,
            order: $order,
            description: $description,
            normalizationContext: $normalizationContext,
            denormalizationContext: $denormalizationContext,
            collectDenormalizationErrors: $collectDenormalizationErrors,
            security: $security,
            securityMessage: $securityMessage,
            securityPostDenormalize: $securityPostDenormalize,
            securityPostDenormalizeMessage: $securityPostDenormalizeMessage,
            securityPostValidation: $securityPostValidation,
            securityPostValidationMessage: $securityPostValidationMessage,
            deprecationReason: $deprecationReason,
            filters: $filters,
            validationContext: $validationContext,
            input: $input,
            output: $output,
            mercure: $mercure,
            messenger: $messenger,
            elasticsearch: $elasticsearch,
            urlGenerationStrategy: $urlGenerationStrategy,
            read: $read,
            deserialize: $deserialize,
            validate: $validate,
            write: $write,
            serialize: $serialize,
            fetchPartial: $fetchPartial,
            forceEager: $forceEager,
            priority: $priority,
            name: $name,
            provider: $provider,
            processor: $processor,
            stateOptions: $stateOptions,
            parameters: $parameters,
            extraProperties: $extraProperties
        );
    }

    public function getItemUriTemplate(): ?string
    {
        return $this->itemUriTemplate;
    }

    public function withItemUriTemplate(string $itemUriTemplate): self
    {
        $self = clone $this;
        $self->itemUriTemplate = $itemUriTemplate;

        return $self;
    }
}
