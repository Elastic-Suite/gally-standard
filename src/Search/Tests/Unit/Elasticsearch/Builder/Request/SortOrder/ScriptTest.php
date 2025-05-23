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

namespace Gally\Search\Tests\Unit\Elasticsearch\Builder\Request\SortOrder;

use Gally\Search\Elasticsearch\Builder\Request\SortOrder\Script;
use Gally\Search\Elasticsearch\Request\SortOrderInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ScriptTest extends KernelTestCase
{
    /**
     * @dataProvider scriptSortOrderDataProvider
     */
    public function testScriptSortOrder(
        string $scriptType,
        string $lang,
        string $source,
        ?array $params,
        ?string $direction,
        ?string $name,
        ?string $missing,
        string $expectedDirection,
        string $expectedMissing,
        array $expectedScript
    ): void {
        $params = [
            'scriptType' => $scriptType,
            'lang' => $lang,
            'source' => $source,
            'params' => $params,
            'direction' => $direction,
            'name' => $name,
            'missing' => $missing,
        ];
        $params = array_filter($params);
        $sortOrder = new Script(...$params); // @phpstan-ignore-line

        $this->assertEquals(SortOrderInterface::TYPE_SCRIPT, $sortOrder->getType());
        $this->assertEquals($scriptType, $sortOrder->getScriptType());
        $this->assertEquals($name, $sortOrder->getName());
        $this->assertEquals($expectedDirection, $sortOrder->getDirection());
        $this->assertEquals($expectedMissing, $sortOrder->getMissing());
        $this->assertEquals($expectedScript, $sortOrder->getScript());
    }

    protected function scriptSortOrderDataProvider(): array
    {
        return [
            [
                'number',               // script type.
                'painless',             // lang.
                "Math.log10p(doc['popularity'].value)", // source.
                null,                   // params.
                null,                   // direction.
                null,                   // name.
                null,                   // missing.
                SortOrderInterface::SORT_ASC,       // expected direction.
                SortOrderInterface::MISSING_LAST,   // expected missing.
                [   // expected script.
                    'lang' => 'painless',
                    'source' => "Math.log10p(doc['popularity'].value)",
                    'params' => [],
                ],
            ],
            [
                'number',               // script type.
                'painless',             // lang.
                "Math.log10p(doc['popularity'].value)", // source.
                null,                   // params.
                SortOrderInterface::SORT_ASC,   // direction.
                'my sort order',        // name.
                SortOrderInterface::MISSING_FIRST,  // missing.
                SortOrderInterface::SORT_ASC,       // expected direction.
                SortOrderInterface::MISSING_FIRST,  // expected missing.
                [   // expected script.
                    'lang' => 'painless',
                    'source' => "Math.log10p(doc['popularity'].value)",
                    'params' => [],
                ],
            ],
            [
                'number',               // script type.
                'painless',             // lang.
                "Math.log10p(doc['popularity'].value)", // source.
                null,                   // params.
                SortOrderInterface::SORT_DESC,  // direction.
                null,                   // name.
                null,                   // missing.
                SortOrderInterface::SORT_DESC,      // expected direction.
                SortOrderInterface::MISSING_FIRST,  // expected missing.
                [   // expected script.
                    'lang' => 'painless',
                    'source' => "Math.log10p(doc['popularity'].value)",
                    'params' => [],
                ],
            ],
            [
                'number',               // script type.
                'painless',             // lang.
                "doc['popularity'].value * params.factor", // source.
                ['factor' => 1.1],      // params.
                null,                   // direction.
                'my sort order',        // name.
                SortOrderInterface::MISSING_FIRST,  // missing.
                SortOrderInterface::SORT_ASC,       // expected direction.
                SortOrderInterface::MISSING_FIRST,  // expected missing.
                [   // expected script.
                    'lang' => 'painless',
                    'source' => "doc['popularity'].value * params.factor",
                    'params' => ['factor' => 1.1],
                ],
            ],
            [
                'number',               // script type.
                'painless',             // lang.
                "doc['popularity'].value * params.factor", // source.
                ['factor' => 1.1],      // params.
                SortOrderInterface::SORT_ASC,   // direction.
                null,                   // name.
                SortOrderInterface::MISSING_LAST,   // missing.
                SortOrderInterface::SORT_ASC,       // expected direction.
                SortOrderInterface::MISSING_LAST,   // expected missing.
                [   // expected script.
                    'lang' => 'painless',
                    'source' => "doc['popularity'].value * params.factor",
                    'params' => ['factor' => 1.1],
                ],
            ],
            [
                'number',               // script type.
                'painless',             // lang.
                "doc['popularity'].value * params.factor", // source.
                ['factor' => 1.1],      // params.
                SortOrderInterface::SORT_DESC,  // direction.
                'my sort order',        // name.
                SortOrderInterface::MISSING_FIRST,  // missing.
                SortOrderInterface::SORT_DESC,      // expected direction.
                SortOrderInterface::MISSING_FIRST,  // expected missing.
                [   // expected script.
                    'lang' => 'painless',
                    'source' => "doc['popularity'].value * params.factor",
                    'params' => ['factor' => 1.1],
                ],
            ],
        ];
    }
}
