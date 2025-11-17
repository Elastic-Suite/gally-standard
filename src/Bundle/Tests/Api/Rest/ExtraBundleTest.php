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

namespace Gally\Bundle\Tests\Api\Rest;

use Gally\Test\AbstractTestCase;
use Gally\Test\ExpectedResponse;
use Gally\Test\RequestToTest;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ExtraBundleTest extends AbstractTestCase
{
    public function testGetCollection(): void
    {
        $request = new RequestToTest('GET', 'extra_bundles', null);
        $expectedResponse = new ExpectedResponse(
            200,
            function (ResponseInterface $response) {
                $this->assertJsonContains([
                    '@context' => $this->getRoute('contexts/ExtraBundle'),
                    '@id' => $this->getRoute('extra_bundles'),
                    '@type' => 'hydra:Collection',
                    'hydra:member' => [],
                ]);
            }
        );

        $this->validateApiCall($request, $expectedResponse);
    }
}
