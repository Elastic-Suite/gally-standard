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

use Gally\Job\Entity\Job;
use Gally\Job\Repository\Job\LogRepository;
use Gally\Job\Repository\JobRepository;
use Gally\Job\Service\JobManager;
use Gally\Test\AbstractTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class AbstractTestJob extends AbstractTestCase
{
    protected static JobManager $jobManager;

    protected static LogRepository $logRepository;

    protected static JobRepository $jobRepository;

    protected static TranslatorInterface $translator;

    protected function setUp(): void
    {
        parent::setUp();

        // Init variables here to avoid issues with the entity manager
        self::$jobManager = static::getContainer()->get(JobManager::class);
        self::$jobRepository = static::getContainer()->get(JobRepository::class);
        self::$logRepository = static::getContainer()->get(LogRepository::class);
        self::$translator = static::getContainer()->get(TranslatorInterface::class);
    }

    protected function getJobObject(int|Job $job): Job
    {
        if (!$job instanceof Job) {
            $job = self::$jobRepository->find($job);
        }

        return $job;
    }

    protected function runJob(int|Job $job): Job
    {
        $jobObject = $this->getJobObject($job);
        self::$jobManager->processJob($jobObject);

        return $jobObject;
    }

    /**
     * Run the job, then assert its logs. Convenient when checking the logs is the whole test;
     * when it is only part of one, prefer runJob() followed by assertJobHasLogMessage() so the
     * run is visible at the call site instead of hiding inside an assertion.
     */
    protected function assertJobLogMessage(int|Job $job, string|array $messages, string $domain = 'gally_job', array $context = []): void
    {
        $this->assertJobHasLogMessage($this->runJob($job), $messages, $domain, $context);
    }

    /**
     * Assert the messages a job has already logged. Does not run anything, so it is safe to call
     * after runJob() without processing the job twice.
     */
    protected function assertJobHasLogMessage(int|Job $job, string|array $messages, string $domain = 'gally_job', array $context = []): void
    {
        $jobObject = $this->getJobObject($job);
        $messages = \is_array($messages) ? $messages : [$messages];
        foreach ($messages as $message) {
            $this->assertTrue(
                self::$logRepository->hasMessage(
                    $jobObject,
                    self::$translator->trans($message, $context, $domain),
                ),
                \sprintf('Job %s did not log: %s', $jobObject->getId(), $message),
            );
        }
    }

    protected function assertJobCsvEqual(Job $job, string $expectedPath): void
    {
        $exportPath = self::$jobManager->getAbsoluteJobFilePath($job);
        $this->assertCsvEquals($exportPath, $expectedPath);
    }

    /**
     * Compare two CSV files ignoring order of rows.
     *
     * @param string $actualPath   Path to actual CSV file
     * @param string $expectedPath Path to expected CSV file
     */
    protected function assertCsvEquals(string $actualPath, string $expectedPath): void
    {
        $actual = array_map(fn ($line) => str_getcsv($line, escape: '\\'), file($actualPath));
        $expected = array_map(fn ($line) => str_getcsv($line, escape: '\\'), file($expectedPath));

        $this->assertCount(\count($expected), $actual, 'CSV files have different number of rows');

        $actualHeader = array_shift($actual);
        $expectedHeader = array_shift($expected);
        $this->assertEquals($expectedHeader, $actualHeader, 'CSV headers are different');

        sort($actual);
        sort($expected);

        $this->assertEquals($expected, $actual, 'CSV content is different');
    }
}
