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

namespace Gally\Configuration\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Gally\Configuration\Entity\Configuration;
use Gally\Configuration\Repository\ConfigurationRepository;
use Gally\Configuration\Validator\ConfigurationDataValidator;
use Gally\Metadata\Operation\Bulk;

class ConfigurationProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private ProcessorInterface $removeProcessor,
        private ConfigurationDataValidator $validator,
        private ConfigurationRepository $configurationRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return Configuration|Configuration[]|null
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Configuration|array|null
    {
        if ($operation instanceof DeleteOperationInterface) {
            return $this->removeProcessor->process($data, $operation, $uriVariables, $context);
        }

        if ($operation instanceof Bulk) {
            $paths = [];
            $configurations = [];
            foreach ($data as $item) {
                $configuration = new Configuration();
                if (\array_key_exists('path', $item)) {
                    $configuration->setPath($item['path']);
                }
                if (\array_key_exists('scope_type', $item)) {
                    $configuration->setScopeType($item['scope_type']);
                }
                $configuration->setValue($item['value'] ?? null);
                $configuration->setScopeCode($item['scope_code'] ?? null);

                if ($configuration->getPath()) {
                    $paths[] = $configuration->getPath();
                }
                $configurations[] = $configuration;
            }

            return $this->persistMultiple($paths, $configurations);
        }

        if ($data instanceof Configuration) {
            $this->validator->validateObject($data);
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }

    /**
     * @param string[]        $paths
     * @param Configuration[] $configurations
     *
     * @return Configuration[]
     */
    public function persistMultiple(array $paths, array $configurations): array
    {
        $existingConfigs = $this->configurationRepository->findBy(['path' => $paths]);
        $sortedConfigs = [];
        $errors = [];

        foreach ($existingConfigs as $existingConfig) {
            if (!isset($sortedConfigs[$existingConfig->getPath()][$existingConfig->getScopeType()])) {
                $sortedConfigs[$existingConfig->getPath()][$existingConfig->getScopeType()] = [];
            }
            $sortedConfigs[$existingConfig->getPath()][$existingConfig->getScopeType()][$existingConfig->getScopeCode()] = $existingConfig;
        }

        foreach ($configurations as $index => $config) {
            $value = $config->getDecodedValue();
            $config = $sortedConfigs[$config->getPath()][$config->getScopeType()][$config->getScopeCode()]
                ?? $config;
            $config->setValue($value);
            $configurations[$index] = $config;

            try {
                $this->validator->validateObject($config);
                $this->entityManager->persist($config);
            } catch (\Exception $exception) {
                $errors[] = \sprintf('Option #%d: %s', $index, $exception->getMessage());
            }
        }

        if (!empty($errors)) {
            throw new InvalidArgumentException(implode(' ', $errors));
        }

        $this->entityManager->flush();

        return $configurations;
    }
}
