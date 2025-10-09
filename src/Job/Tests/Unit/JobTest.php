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

namespace Gally\Job\Tests\Unit;

use Gally\Exception\LogicException;
use Gally\Job\Message\ProcessJob;
use Gally\Job\Tests\Job\DummyImport;
use Symfony\Component\Messenger\Transport\InMemoryTransport;

class JobTest extends AbstractTestJob
{
    protected function setUp(): void
    {
        parent::setUp();

        static::copyDirectoryFiles(
            __DIR__ . '/../fixtures/jobs/files/', self::$jobManager->getJobDirectoryPath(),
        );
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::loadFixture([
            __DIR__ . '/../fixtures/jobs/jobs.yaml',
        ]);
    }

    public function testJobsAreQueuedOnCreation(): void
    {
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.async');

        $envelopes = $transport->get();
        $this->assertNotEmpty($envelopes);

        /** @var ProcessJob $message */
        $message = $envelopes[0]->getMessage();
        $this->assertInstanceOf(ProcessJob::class, $message);
        $this->assertEquals(1, $message->getJobId());
    }

    public function testFileEmpty(): void
    {
        $this->assertJobLogMessage(1, 'import.error.file_not_found');
    }

    public function testWrongHeaders(): void
    {
        // Wrong header.
        $this->assertJobLogMessage(3, 'import.error.invalid_headers', 'gally_job', ['%expected%' => implode(', ', DummyImport::CSV_HEADERS)]);
        // Additional header.
        $this->assertJobLogMessage(4, 'import.error.invalid_headers', 'gally_job', ['%expected%' => implode(', ', DummyImport::CSV_HEADERS)]);
    }

    public function testWrongLineContent(): void
    {
        $this->assertJobLogMessage(5, 'import.error.column_count_mismatch', 'gally_job', ['%line%' => 2, '%expected%' => \count(DummyImport::CSV_HEADERS), '%actual%' => 3]);
    }

    public function testInvalidJobId(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            self::$translator->trans('job.error.not_found', ['%id%' => 87], 'gally_job')
        );
        self::$jobManager->processByJobId(87);
    }

    public function testJobCanBeProcesses(): void
    {
        $job = self::$jobRepository->find(5);
        $this->assertJobLogMessage($job, 'job.error.cannot_be_launched', 'gally_job', ['%id%' => $job->getId(), '%status%' => $job->getStatus()]);
    }

    public function testWrongImportExport(): void
    {
        $job = self::$jobRepository->find(6);
        $this->assertJobLogMessage($job, 'job.error.no_import_handler', 'gally_job', ['%profile%' => $job->getProfile(), '%id%' => $job->getId()]);

        $job = self::$jobRepository->find(7);
        $this->assertJobLogMessage($job, 'job.error.no_export_handler', 'gally_job', ['%profile%' => $job->getProfile(), '%id%' => $job->getId()]);
    }
}
