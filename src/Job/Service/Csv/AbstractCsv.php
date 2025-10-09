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

namespace Gally\Job\Service\Csv;

use Gally\Job\Entity\Job;
use Gally\Job\Exception\JobException;
use Gally\Job\Service\JobManager;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractCsv
{
    public const VALUE_SEPARATOR = ';';
    public const MULTI_VALUE_SEPARATOR = ',';
    public const KEY_VALUE_SEPARATOR = ':';

    public const BOOLEAN_VALUE_TRUE = 'true';
    public const BOOLEAN_VALUE_FALSE = 'false';

    public const SYNONYM_TYPE = 'synonym';
    public const EXPANSION_TYPE = 'expansion';

    public const SCOPE_TYPE_LOCALIZED_CATALOG = 'localized_catalog';
    public const SCOPE_TYPE_LOCALE = 'locale';

    protected ?Job $currentJob = null;

    public function __construct(
        protected TranslatorInterface $translator,
        protected JobManager $jobManager,
        protected string $jobProfile,
    ) {
    }

    public function setCurrentJob(?Job $job): void
    {
        $this->currentJob = $job;
    }

    public function supports(string $profile): bool
    {
        return $this->jobProfile === $profile;
    }

    public function getProfile(): string
    {
        return $this->jobProfile;
    }

    protected function isCurrentJobSet(): void
    {
        if (null === $this->currentJob) {
            throw new JobException($this->translator->trans('job.error.current_job_not_set', [], 'gally_job'));
        }
    }

    protected function logInfo(string $message, string $domain, array $context = []): void
    {
        $translatedMessage = $this->translator->trans($message, $context, $domain);
        $this->jobManager->logInfo($this->currentJob, $translatedMessage);
    }

    protected function logError(string $message, string $domain, array $context = []): void
    {
        $translatedMessage = $this->translator->trans($message, $context, $domain);
        $this->jobManager->logError($this->currentJob, $translatedMessage);
    }
}
