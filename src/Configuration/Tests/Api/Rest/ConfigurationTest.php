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

namespace Gally\Configuration\Tests\Api\Rest;

use Gally\Test\AbstractTestCase;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestToTest;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ConfigurationTest extends AbstractTestCase
{
    public function testGetCollection(): void
    {
        $request = new RequestToTest('GET', 'configurations', null);
        $expectedResponse = new ExpectedResponse(
            200,
            function (ResponseInterface $response) {
                $this->assertJsonContains([
                    '@context' => $this->getRoute('contexts/Configuration'),
                    '@id' => $this->getRoute('configurations'),
                    '@type' => 'hydra:Collection',
                    'hydra:member' => [['id' => 'base_url/media']],
                ]);
            }
        );

        $this->validateApiCall($request, $expectedResponse);
    }
}
