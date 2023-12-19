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

namespace Gally\Search\Tests\Unit\GraphQl\Type\Definition;

use Gally\Search\Elasticsearch\Request\Container\Configuration\ContainerConfigurationProvider;
use Gally\Search\GraphQl\Type\Definition\RequestTypeEnumType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RequestTypeEnumTypeTest extends KernelTestCase
{
    /**
     * Check if config does not contain internal request types.
     */
    public function testConfig(): void
    {
        $containerConfigurationProvider = static::getContainer()->get(ContainerConfigurationProvider::class);
        \assert(static::getContainer()->get(ContainerConfigurationProvider::class) instanceof ContainerConfigurationProvider);

        $requestTypeEnumType = new RequestTypeEnumType($containerConfigurationProvider);

        $config = $requestTypeEnumType->getConfig();
        $internalRequestTypes = $containerConfigurationProvider->getInternalRequestTypes();
        $this->assertNotCount(0, $internalRequestTypes);
        $this->assertArrayHasKey('values', $config);
        $this->assertNotCount(0, $config['values']);
        foreach ($internalRequestTypes as $internalRequestType) {
            $this->assertNotContains($internalRequestType, $config['values']);
        }
    }
}
