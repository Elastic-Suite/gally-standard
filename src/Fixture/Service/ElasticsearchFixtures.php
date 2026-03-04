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

namespace Gally\Fixture\Service;

use Gally\Index\Repository\Document\DocumentRepositoryInterface;
use Gally\Index\Repository\Index\IndexRepositoryInterface;
use Symfony\Component\Validator\Constraints\Json;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ElasticsearchFixtures implements ElasticsearchFixturesInterface
{
    /**
     * ElasticsearchFixtures constructor.
     */
    public function __construct(
        private ValidatorInterface $validator,
        private IndexRepositoryInterface $indexRepository,
        private DocumentRepositoryInterface $documentRepository,
        private string $env,
        private bool $testMode,
    ) {
        if ('test' === $this->env) {
            $this->setTestMode(true);
        }
    }

    /**
     * Get test mode.
     */
    public function isTestMode(): bool
    {
        return $this->testMode;
    }

    /**
     * Set test mode.
     */
    public function setTestMode(bool $testMode): void
    {
        $this->testMode = $testMode;
    }

    /**
     * Create indices from data in $pathFiles.
     */
    public function loadFixturesIndexFiles(array $pathFiles): void
    {
        $this->validateFilesFormat($pathFiles);

        foreach ($pathFiles as $file) {
            $indices = file_get_contents($file);
            $indices = json_decode($indices, true);
            foreach ($indices as $index) {
                $this->indexRepository->create(
                    $this->getTestIndexName($index['name']),
                    $index['settings'] ?? [],
                    \array_key_exists('alias', $index) ? [$this->getTestAliasName($index['alias'])] : []
                );
            }
        }
    }

    /**
     * Create documents from data in $pathFiles.
     */
    public function loadFixturesDocumentFiles(array $pathFiles, ?\DateTime $referenceDate = null): void
    {
        $this->validateFilesFormat($pathFiles);
        $referenceDate = $referenceDate ?? new \DateTime('now', new \DateTimeZone('UTC'));

        foreach ($pathFiles as $file) {
            $documents = file_get_contents($file);

            $datePatterns = $this->extractDatePatterns($documents, $referenceDate);
            $documents = str_replace(array_keys($datePatterns), array_values($datePatterns), $documents);

            $documents = json_decode($documents, true);
            foreach ($documents as $document) {
                $this->documentRepository->index(
                    $this->getTestIndexName($document['index_name']),
                    array_map('json_encode', $document['documents']),
                    true
                );
            }
        }
    }

    /**
     * Delete test indices.
     */
    public function deleteTestFixtures(): void
    {
        $this->indexRepository->delete(self::PREFIX_TEST_INDEX . '*');
    }

    /**
     * Get index name prefixed by the index test prefix.
     */
    public function getTestIndexName(string $indexName): string
    {
        return $this->isTestMode() ? self::PREFIX_TEST_INDEX . $indexName : $indexName;
    }

    /**
     * Get index name prefixed by the index test prefix.
     */
    public function getTestAliasName(string $aliasName): string
    {
        return $this->isTestMode() ? self::PREFIX_TEST_INDEX . $aliasName : $aliasName;
    }

    /**
     * Extract and generate date patterns only for dates that exist in the JSON string.
     */
    private function extractDatePatterns(string $jsonContent, \DateTime $referenceDate): array
    {
        preg_match_all('/@now:([+-]?\d+)d/', $jsonContent, $matches);

        if (empty($matches[1])) {
            return [];
        }

        $patterns = [];
        $uniqueDays = array_unique($matches[1]);

        foreach ($uniqueDays as $dayString) {
            $days = (int) $dayString;
            $date = clone $referenceDate;
            $date->modify(\sprintf('%+d days', $days));
            $patterns[\sprintf('@now:%sd', $dayString)] = $date->format('Y-m-d H:i:s');
        }

        return $patterns;
    }

    /**
     * Validate files format.
     */
    protected function validateFilesFormat(array $pathFiles): array
    {
        $data = [];
        $errorsByFile = [];
        foreach ($pathFiles as $file) {
            $item = file_get_contents($file);
            $errors = $this->validator->validate($item, new Json());
            if ($errors->count() > 0) {
                $errorsByFile[$file] = $errors;
            }
        }

        if (!empty($errorsByFile)) {
            $this->throwFormatException($errorsByFile);
        }

        return $data;
    }

    /**
     * Format exception message and throw format exception.
     *
     * @throws \Exception
     */
    protected function throwFormatException(array $errorsByFile)
    {
        $messages = [];
        foreach ($errorsByFile as $file => $errors) {
            $messages = array_map(
                function (ConstraintViolation $violation) use ($file) {
                    return $file . ' => ' . $violation->getMessage();
                },
                iterator_to_array($errors->getIterator())
            );
        }

        throw new \Exception('Format error(s) on fixture files: ' . \PHP_EOL . implode(\PHP_EOL, $messages));
    }
}
