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

namespace Gally\Job\Service;

use Doctrine\ORM\EntityManagerInterface;
use Gally\Exception\LogicException;
use Gally\Job\Entity\Job;
use Gally\Job\Exception\JobException;
use Gally\Job\Repository\JobRepository;
use Psr\Log\LogLevel;
use Symfony\Contracts\Translation\TranslatorInterface;

class JobManager
{
    public const JOB_DIRECTORY_PATH = 'var/job';

    /**
     * @param JobExportInterface[] $exports
     * @param JobImportInterface[] $imports
     */
    public function __construct(
        private JobRepository $jobRepository,
        private EntityManagerInterface $entityManager,
        private string $projectDir,
        private TranslatorInterface $translator,
        private iterable $exports,
        private iterable $imports,
    ) {
    }

    public function processByJobId(int $jobId): void
    {
        $job = $this->jobRepository->find($jobId);
        if ($job  instanceof Job) {
            $this->processJob($job);
        } else {
            throw new LogicException($this->translator->trans('job.error.not_found', ['%id%' => $jobId], 'gally_job'));
        }
    }

    public function processJob(Job $job): void
    {
        $error = false;
        $this->logInfo($job, $this->translator->trans('job.start', [], 'gally_job'));
        try {
            $this->jobCanBeProcessed($job);
            $this->setAsProcessing($job);
            $this->save($job);

            if (Job::TYPE_IMPORT === $job->getType()) {
                $this->processImport($job);
            } elseif (Job::TYPE_EXPORT === $job->getType()) {
                $this->processExport($job);
            } else {
                $error = true;
                $this->logError(
                    $job,
                    $this->translator->trans('job.error.unsupported_type', ['%type%' => $job->getType(), '%id%' => $job->getId()], 'gally_job')
                );
            }
        } catch (JobException $e) {
            $error = true;
            $this->logs(
                $job,
                [
                    ['message' => $e->getMessage(), 'severity' => LogLevel::ERROR],
                    ['message' => $this->translator->trans('job.error.execution_failed', [], 'gally_job'), 'severity' => LogLevel::ERROR],
                ]
            );
        } catch (\Throwable $e) {
            $error = true;
            $this->logs(
                $job,
                [
                    ['message' => $e->getMessage(), 'severity' => LogLevel::ERROR],
                    ['message' => $e->getTraceAsString(), 'severity' => LogLevel::ERROR],
                    ['message' => $this->translator->trans('job.error.execution_failed', [], 'gally_job'), 'severity' => LogLevel::ERROR],
                ]
            );
        } finally {
            $this->setAsFinished($job);
            if ($error) {
                $this->setAsFailed($job);
            }
            $this->save($job);
            $this->logInfo($job, $this->translator->trans('job.end', [], 'gally_job'));
        }
    }

    public function logInfo(Job $job, string $message): void
    {
        $this->log($job, $message);
    }

    public function logDebug(Job $job, string $message): void
    {
        $this->log($job, $message, LogLevel::DEBUG);
    }

    public function logError(Job $job, string $message): void
    {
        $this->log($job, $message, LogLevel::ERROR);
    }

    /**
     * @param array<array{message: string, severity?: string}> $logs
     */
    public function logs(Job $job, array $logs): void
    {
        foreach ($logs as $log) {
            $logEntity = $this->getLogObject($job, $log['message'], $log['severity'] ?? null);
            $this->entityManager->persist($logEntity);
        }
        $this->entityManager->flush();
    }

    public function log(Job $job, string $message, string $severity = LogLevel::INFO): void
    {
        $log = $this->getLogObject($job, $message, $severity);
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    public function getAbsoluteJobFilePath(Job $job): string
    {
        return $this->getAbsoluteJobFilePathByFileName($job->getFile()->filePath);
    }

    public function getAbsoluteJobFilePathByFileName(string $fileName): string
    {
        return \sprintf('%s/%s',
            rtrim($this->getJobDirectoryPath(), '/'),
            ltrim($fileName, '/')
        );
    }

    public function getJobDirectoryPath(): string
    {
        return \sprintf('%s/%s',
            $this->projectDir,
            self::JOB_DIRECTORY_PATH,
        );
    }

    public function save(Job $job): void
    {
        $this->entityManager->persist($job);
        $this->entityManager->flush();
    }

    public function setAsProcessing(Job $job): void
    {
        $job->setStatus(Job::STATUS_PROCESSING);
    }

    public function setAsFailed(Job $job): void
    {
        $job->setStatus(Job::STATUS_FAILED);
    }

    public function setAsFinished(Job $job): void
    {
        $job->setStatus(Job::STATUS_FINISHED);
        $job->setFinishedAt(new \DateTime());
    }

    public function updateJobFile(Job $job, $fileName): void
    {
        $jobFile = $job->getFile();
        if (!$jobFile instanceof Job\File) {
            $jobFile = new Job\File();
        }
        $jobFile->filePath = $fileName;
        $job->setFile($jobFile);
    }

    public function getProfiles(): array
    {
        $profiles = [];
        foreach ($this->imports as $import) {
            $profiles[Job::TYPE_IMPORT][$import->getProfile()] = [
                'profile' => $import->getProfile(),
                'label' => $import->getLabel(),
            ];
        }

        foreach ($this->exports as $export) {
            $profiles[Job::TYPE_EXPORT][$export->getProfile()] = [
                'profile' => $export->getProfile(),
                'label' => $export->getLabel(),
            ];
        }

        return $profiles;
    }

    public function getProfileOptions(): array
    {
        $profiles = [];
        foreach ($this->getProfiles() as $profilesGrouped) {
            foreach ($profilesGrouped as $profile) {
                $profiles[] = [
                    'id' => $profile['profile'],
                    'value' => $profile['profile'],
                    'label' => $profile['label'],
                ];
            }
        }

        return $profiles;
    }

    protected function jobCanBeProcessed(Job $job): void
    {
        if (Job::STATUS_NEW !== $job->getStatus()) {
            throw new JobException($this->translator->trans('job.error.cannot_be_launched', ['%id%' => $job->getId(), '%status%' => $job->getStatus()], 'gally_job'));
        }
    }

    protected function getLogObject(Job $job, string $message, string $severity = LogLevel::INFO): Job\Log
    {
        $log = new Job\Log();
        $log->setJob($job);
        $log->setSeverity($severity);
        $log->setMessage($message);
        $log->setLoggedAt(new \DateTime());

        return $log;
    }

    protected function processImport(Job $job): void
    {
        foreach ($this->imports as $import) {
            if ($import->supports($job->getProfile())) {
                $import->setCurrentJob($job);
                $import->validateImportFile();
                $import->process();

                return;
            }
        }

        throw new JobException($this->translator->trans('job.error.no_import_handler', ['%profile%' => $job->getProfile(), '%id%' => $job->getId()], 'gally_job'));
    }

    protected function processExport(Job $job): void
    {
        foreach ($this->exports as $export) {
            if ($export->supports($job->getProfile())) {
                $export->setCurrentJob($job);
                $export->process();

                return;
            }
        }

        throw new JobException($this->translator->trans('job.error.no_export_handler', ['%profile%' => $job->getProfile(), '%id%' => $job->getId()], 'gally_job'));
    }
}
