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

namespace Gally\GraphQl\Error;

use ApiPlatform\GraphQl\Error\ErrorHandlerInterface;
use Psr\Log\LoggerInterface;

class ErrorHandler implements ErrorHandlerInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private ErrorHandlerInterface $defaultErrorHandler,
    ) {
    }

    public function __invoke(array $errors, callable $formatter): array
    {
        // Log exceptions in GraphQl context because it's not done by default.
        foreach ($errors as $error) {
            $exception = $error->getPrevious() ?? $error;
            $this->logger->critical(
                '[GraphQl] Internal server error.',
                [
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'path' => $error->getPath(),
                    'trace' => $exception->getTraceAsString(),
                ]
            );
        }

        return ($this->defaultErrorHandler)($errors, $formatter);
    }
}
