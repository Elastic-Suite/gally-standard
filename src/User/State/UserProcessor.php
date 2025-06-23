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

namespace Gally\User\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use CoopTilleuls\ForgotPasswordBundle\Manager\ForgotPasswordManager;
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderChainInterface;
use Doctrine\ORM\EntityManagerInterface;
use Gally\Catalog\Entity\LocalizedCatalog;
use Gally\Catalog\Repository\LocalizedCatalogRepository;
use Gally\User\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private ProcessorInterface $removeProcessor,
        private UserPasswordHasherInterface $passwordHasher,
        private ForgotPasswordManager $forgotPasswordManager,
        private ProviderChainInterface $providerChain,
    ) {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): ?User
    {
        if ($operation instanceof DeleteOperationInterface) {
            return $this->removeProcessor->process($data, $operation, $uriVariables, $context);
        }

        $resetPassword = false;
        /** @var User $data */
        if ($data->getPassword() === null) {
            $data->setPassword(
                $this->passwordHasher->hashPassword($data, uniqid())
            );
            $resetPassword = true;
        }

        $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        if ($resetPassword) {
            // reset password and send "reset password" email.
            $this->forgotPasswordManager->resetPassword('email', $data->getEmail(), $this->providerChain->get());
        }

        return $result;
    }

}
