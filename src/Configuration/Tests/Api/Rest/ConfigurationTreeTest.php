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
use Gally\User\Constant\Role;
use Gally\User\Entity\User;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ConfigurationTreeTest extends AbstractTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::loadFixture([
            __DIR__ . '/../../fixtures/configurations.yaml',
            __DIR__ . '/../../fixtures/catalogs.yaml',
        ]);
    }

    /**
     * @dataProvider configurationTreeDataProvider
     */
    public function testConfigurationTree(
        ?User $user,
        int $expectedResponseCode,
        array $expectedConfigurationTree,
    ): void {
        $this->validateApiCall(
            new RequestToTest('GET', 'configuration_tree', $user),
            new ExpectedResponse(
                $expectedResponseCode,
                function (ResponseInterface $response) use ($expectedConfigurationTree) {
                    $data = $response->toArray();
                    $this->assertJsonContains(['configTree' => $expectedConfigurationTree]);
                }
            )
        );
    }

    public function configurationTreeDataProvider(): iterable
    {
        yield [null, 401, []];
        yield [
            $this->getUser(Role::ROLE_CONTRIBUTOR),
            200,
            [
                'scopes' => [
                    'language' => [
                        'label' => 'Languages',
                        'options' => [
                            'values' => [
                                ['value' => 'fr', 'label' => 'French'],
                                ['value' => 'en', 'label' => 'English'],
                            ],
                        ],
                    ],
                    'locale' => [
                        'label' => 'Locales',
                        'options' => [
                            'api_rest' => '/boost_model_options',
                            'api_graphql' => 'boostModelOptions',
                        ],
                    ],
                ],
                'groups' => [
                    'general' => [
                        'scopeType' => 'locale',
                        'fieldsets' => [
                            'general' => [
                                'label' => 'General',
                                'position' => 10,
                                'tooltip' => 'gally_configuration.group.general.fieldset.general.toolTip',
                                'fields' => [
                                    'gally.base_url.media' => [
                                        'label' => 'Media base url',
                                        'position' => 10,
                                        'input' => 'string',
                                        'visible' => true,
                                        'editable' => true,
                                        'placeholder' => 'gally_configuration.gally_base_url_media.placeholder',
                                        'infoTooltip' => 'gally_configuration.gally_base_url_media.toolTip',
                                        'rangeDateType' => 'from',
                                        'rangeDateId' => 'createdAt',
                                        'options' => [
                                            'objectKeyValue' => 'locale',
                                            'api_rest' => '/boost_model_options',
                                            'api_graphql' => 'boostModelOptions',
                                            'values' => [
                                                ['value' => 'synonym', 'label' => 'Synonym'],
                                                ['value' => 'expansion', 'label' => 'Expansion'],
                                            ],
                                        ],
                                        'depends' => [
                                            [
                                                'type' => 'visible',
                                                'conditions' => [
                                                    ['field' => 'scopeType', 'value' => 'test'],
                                                ],
                                            ],
                                        ],
                                        'multipleInputConfiguration' => [
                                            'inputDependencies' => [
                                                [
                                                    'field' => 'model',
                                                    'value' => 'constant_score',
                                                    'jsonKeyValue' => 'constant_score_value',
                                                    'input' => 'slider',
                                                ],
                                                [
                                                    'field' => 'model',
                                                    'value' => 'attribute_value',
                                                    'jsonKeyValue' => 'attribute_value_config',
                                                    'input' => 'proportionalToAttribute',
                                                ],
                                            ],
                                        ],
                                        'validation' => ['min' => 10, 'max' => 100],
                                        'multipleValueFormat' => ['maxCount' => 3],
                                        'requestTypeConfigurations' => [
                                            'operatorOptionsApi' => 'boost_query_text_operator_options',
                                            'limitationTypeOptionsApi' => 'boost_limitation_type_options',
                                            'requestTypeOptionsApi' => 'boost_request_type_options',
                                        ],
                                    ],
                                ],
                            ],
                            'preview' => [
                                'label' => 'preview',
                                'position' => 20,
                                'external' => true,
                            ],
                        ],
                        'label' => 'General',
                    ],
                ],
            ],
        ];
    }
}
