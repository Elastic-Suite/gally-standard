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
use Doctrine\ORM\EntityNotFoundException;
use Gally\Exception\LogicException;
use Gally\Job\Entity\Job;
use Psr\Log\LogLevel;

// /RAF Objectif avoir un import de thésaurus qui fonctionne
// / Mettre en place le system de message pour lire les jobs
// / Mettre en place le cron
// / Mettre en place l'import de thesaurus
// / Voir comment gérer le fait que l'on affiche ou pas dans le menu, il faudra ptt déplacer tout le code  dans le premium
// /
// /
// / php bin/console messenger:consume --all --limit=10 --failure-limit

class JobManager
{
    /**
     * @param JobExportInterface[] $exports
     * @param JobImportInterface[] $imports
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private iterable $exports,
        private iterable $imports,
    ) {
    }

    /**
     * @throws EntityNotFoundException
     */
    public function processByJobId(int $jobId): void
    {
        $job = $this->entityManager->getRepository(Job::class)->find($jobId);
        if ($job  instanceof Job) {
            $this->processJob($job);
        } else {
            throw new EntityNotFoundException("The job with id '{$jobId}' was not found");
        }
    }

    public function processJob(Job $job): void
    {
        $error = false;
        $this->log($job, 'START Job');
        try {
            $this->jobCanBeProcessed($job);
            $this->setAsProcessing($job);
            $this->save($job);

            if (Job::TYPE_IMPORT === $job->getType()) {
                $this->processImport($job);
            } elseif (Job::TYPE_EXPORT === $job->getType()) {
                $this->processExport($job);
            } else {
                throw new LogicException(\sprintf('Unsupported job type "%s" (Job id: %d)', $job->getType(), $job->getId()));
            }
        } catch (\Exception $e) {
            $error = true;
            $this->logs(
                $job,
                [
                    ['message' => 'An error occurred while executing the job.', 'severity' => LogLevel::ERROR],
                    ['message' => $e->getMessage(), 'severity' => LogLevel::ERROR],
                    ['message' => $e->getTraceAsString(), 'severity' => LogLevel::ERROR],
                ]
            );
        }

        $this->setAsFinished($job);
        if ($error) {
            $this->setAsFailed($job);
        }
        $this->save($job);
        $this->log($job, 'END Job');
    }

    /**
     * @param array<array{message: string, severity?: string}> $logs
     */
    public function logs(Job $job, array $logs)
    {
        foreach ($logs as $log) {
            $logEntity = $this->getLogObject($job, $log['message'], $log['severity'] ?? null);
            $this->entityManager->persist($logEntity);
        }
        $this->entityManager->flush();
    }

    // todo: Dans l'entite log changer log par message.
    public function log(Job $job, string $message, string $severity = LogLevel::INFO): void
    {
        $log = $this->getLogObject($job, $message, $severity);
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    public function save($job): void
    {
        $this->entityManager->persist($job);
        $this->entityManager->flush();
    }

    protected function jobCanBeProcessed(Job $job): void
    {
        if (Job::STATUS_NEW !== $job->getStatus()) {
            throw new LogicException(\sprintf('The job with id "%d" cannot be launched because its current status is "%s"', $job->getId(), $job->getStatus()));
        }
    }

    protected function setAsFailed(Job $job): void
    {
        $job->setStatus(Job::STATUS_FAILED);
    }

    protected function setAsProcessing(Job $job): void
    {
        $job->setStatus(Job::STATUS_PROCESSING);
    }

    protected function setAsFinished(Job $job): void
    {
        $job->setStatus(Job::STATUS_FINISHED);
        $job->setFinishedAt(new \DateTime());
    }

    protected function getLogObject(Job $job, string $message, string $severity = LogLevel::INFO): Job\Log
    {
        $log = new Job\Log();
        $log->setJob($job);
        $log->setSeverity($severity);
        $log->setLog($message);
        $log->setLoggedAt(new \DateTime());

        return $log;
    }

    protected function processImport(Job $job): void
    {
        foreach ($this->imports as $import) {
            if ($import->supports($job->getProfile())) {
                $import->validateImportFile($job);
                $import->process($job);

                return;
            }
        }

        throw new LogicException(\sprintf('No import handler found for profile "%s" (Job id: %d)', $job->getProfile(), $job->getId()));
    }

    protected function processExport(Job $job): void
    {
        foreach ($this->exports as $export) {
            if ($export->supports($job->getProfile())) {
                $export->process($job);

                return;
            }
        }

        throw new LogicException(\sprintf('No export handler found for profile "%s" (Job id: %d)', $job->getProfile(), $job->getId()));
    }
}
