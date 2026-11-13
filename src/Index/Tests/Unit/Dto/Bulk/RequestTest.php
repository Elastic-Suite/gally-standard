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

namespace Gally\Index\Tests\Unit\Dto\Bulk;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use Gally\Index\Dto\Bulk\Request;
use Gally\Index\Entity\DataStream;
use Gally\Index\Entity\Index;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testOperationsBodyIsBuiltAtSendWithTheActionOfEachTargetType(): void
    {
        $request = new Request();
        $this->assertTrue($request->isEmpty());

        $request->addDocument(new Index('first_index'), '1', ['name' => 'one']);
        $request->deleteDocument(new Index('first_index'), '2');
        $request->updateDocument(new Index('first_index'), '3', ['name' => 'three']);
        $request->addDocument(new DataStream('my_stream'), '4', ['name' => 'four']);

        $this->assertFalse($request->isEmpty());
        $this->assertSame(
            [
                ['index' => ['_index' => 'first_index', '_id' => '1']],
                ['name' => 'one'],
                ['create' => ['_index' => 'my_stream', '_id' => '4']],
                ['name' => 'four'],
                ['update' => ['_index' => 'first_index', '_id' => '3']],
                ['doc' => ['name' => 'three']],
                ['delete' => ['_index' => 'first_index', '_id' => '2']],
            ],
            $request->getOperations()
        );
    }

    public function testRequestIsEmptyWhenNoTargetHasAnyOperation(): void
    {
        $index = new Index('first_index');
        $request = new Request();
        $request->addDocument($index, '1', ['name' => 'one']);
        $this->assertFalse($request->isEmpty());

        $request->setDocuments($index, []);
        $this->assertTrue($request->isEmpty());

        $request->deleteDocument($index, '2');
        $this->assertFalse($request->isEmpty());
    }

    public function testGettingOperationsOfAnEmptyRequestIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Bulk request is empty.');

        (new Request())->getOperations();
    }

    public function testGettingOperationsOfATargetEmptiedBySetDocumentsIsRejected(): void
    {
        $index = new Index('first_index');
        $request = new Request();
        $request->addDocument($index, '1', ['name' => 'one']);
        $request->setDocuments($index, []);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Bulk request is empty.');

        $request->getOperations();
    }

    public function testAddingTheSameIdOnTheSameTargetReplacesThePreviousDocument(): void
    {
        $index = new Index('first_index');
        $request = new Request();
        $request->addDocument($index, '1', ['name' => 'one']);
        $request->addDocument($index, '1', ['name' => 'one bis']);

        $this->assertSame(
            [['index' => ['_index' => 'first_index', '_id' => '1']], ['name' => 'one bis']],
            $request->getOperations()
        );
    }

    public function testGetDocumentsReturnsDocumentsOfTheTargetById(): void
    {
        $index = new Index('first_index');
        $request = new Request();
        $request->addDocument($index, '1', ['name' => 'one']);
        $request->deleteDocument($index, '2');
        $request->updateDocument($index, '3', ['name' => 'three']);
        $request->addDocument(new Index('second_index'), '4', ['name' => 'four']);
        $request->addDocument($index, 'five', ['name' => 'five']);
        $request->addDocument(new DataStream('my_stream'), '6', ['name' => 'six']);

        $this->assertSame([1 => ['name' => 'one'], 'five' => ['name' => 'five']], $request->getDocuments($index));
        $this->assertSame([6 => ['name' => 'six']], $request->getDocuments(new DataStream('my_stream')));
        $this->assertSame([], $request->getDocuments(new Index('unknown_index')));
    }

    public function testSetDocumentsReplacesAllDocumentsOfTheTargetOnly(): void
    {
        $index = new Index('first_index');
        $request = new Request();
        $request->addDocument($index, '1', ['name' => 'one']);
        $request->addDocument($index, '2', ['name' => 'two']);
        $request->updateDocument($index, '3', ['name' => 'three']);
        $request->addDocument(new Index('second_index'), '4', ['name' => 'four']);

        $request->setDocuments($index, ['2' => ['name' => 'two (modified)'], '5' => ['name' => 'five']]);

        $this->assertSame(['2' => ['name' => 'two (modified)'], '5' => ['name' => 'five']], $request->getDocuments($index));
        $this->assertSame([4 => ['name' => 'four']], $request->getDocuments(new Index('second_index')));
        $this->assertSame(
            [
                ['index' => ['_index' => 'first_index', '_id' => '2']],
                ['name' => 'two (modified)'],
                ['index' => ['_index' => 'first_index', '_id' => '5']],
                ['name' => 'five'],
                ['index' => ['_index' => 'second_index', '_id' => '4']],
                ['name' => 'four'],
                ['update' => ['_index' => 'first_index', '_id' => '3']],
                ['doc' => ['name' => 'three']],
            ],
            $request->getOperations()
        );
    }

    /**
     * @dataProvider missingIdInDataProvider
     */
    public function testMissingIdIsRejectedWhenHandlingSeveralDocuments(string $method): void
    {
        $request = new Request();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Document at position 1 has no id or entity_id.');
        $request->{$method}(new Index('first_index'), [['id' => '1', 'name' => 'one'], ['name' => 'no id']]);
    }

    public function missingIdInDataProvider(): iterable
    {
        yield ['addDocuments'];
        yield ['updateDocuments'];
    }
}
