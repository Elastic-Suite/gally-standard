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
use Gally\Job\Entity\Job;


///RAF Objectif avoir un import de thésaurus qui fonctionne
/// Mettre en place le system de message pour lire les jobs
/// Mettre en place le cron
/// Mettre en place l'import de thesaurus
/// Voir comment gérer le fait que l'on affiche ou pas dans le menu, il faudra ptt déplacer tout le code  dans le premium
///
///
/// php bin/console messenger:consume --all --limit=10 --failure-limit

class JobManager
{
    /**
     * @param ExportInterface[] $exports
     * @param ImportInterface[] $imports
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private iterable $exports,
        private iterable $imports,
    )
    {}


    public function processJob(Job $job): void
    {
        if ($job->getType() === Job::TYPE_EXPORT) {
            foreach ($this->imports as $import) {
                if ($import->supports($job->getProfile())) {
                    $import->process($job);
                    break;
                }
            }
        }
    }

    public function import(ImportInterface $import, Job $job): void
    {
        $this->log($job, 'START Import');
        try {
            $import->process($job);
        } catch (\Exception $e) {
            $this->log($job, 'An error occurred while importing data.', 'error');
            //todo mettre les constantes pour la severity
            $this->logs(
                $job,
                [
                    ['message' => 'An error occurred while importing data.', 'severity' => 'info'],
                    ['message' => $e->getMessage(), 'severity' => 'error'],
                    ['message' => $e->getTraceAsString(), 'severity' => 'error'],
                ]
            );
        }
        $this->log($job, 'END Import');
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

    //todo: Utiliser une constante pour la severity
    //todo: Dans l'entite log changer log par message.
    public function log(Job $job, string $message, string $severity = 'info'): void
    {
        $log = $this->getLogObject($job, $message, $severity);
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    protected function getLogObject(Job $job, string $message, string $severity = 'info'): Job\Log
    {
        $log = new Job\Log();
        $log->setJob($job);
        $log->setSeverity($severity);
        $log->setLog($message);
        $log->setLoggedAt(new \DateTime());

        return $log;
    }
}
