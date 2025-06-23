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

namespace Gally\User\Service;

use CoopTilleuls\ForgotPasswordBundle\Entity\AbstractPasswordToken;
use CoopTilleuls\ForgotPasswordBundle\Manager\ForgotPasswordManager;
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Gally\Configuration\Service\BaseUrlProvider;
use Gally\Email\Service\EmailSender;
use Gally\User\Constant\Role;
use Gally\User\Entity\User;
use Gally\User\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private EmailSender $emailSender,
        private BaseUrlProvider $baseUrlProvider,
    ) {
    }

    public function create(string $email, array $roles, string $password): void
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRoles(empty($roles) ? [Role::ROLE_CONTRIBUTOR] : $roles);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $password)
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function update(string $currentEmail, ?string $email, ?array $roles, ?string $password): void
    {
        /** @var User|null $user */
        $user = $this->userRepository->findOneBy(['email' => $currentEmail]);

        if (!$user instanceof User) {
            throw new EntityNotFoundException("The user with the email '{$currentEmail}' was not found");
        }

        $user->setEmail($email ?? $user->getEmail());
        $user->setRoles($roles ?? $user->getRoles());
        if (null !== $password) {
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, $password)
            );
        }

        $this->entityManager->flush();
    }

    public function isUserExists(string $email): bool
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        return null !== $user;
    }

    public function getRoles(): array
    {
        return Role::ROLES;
    }

    /**
     * Get roles that don't exist from $roles.
     */
    public function getFakeRoles(array $roles): array
    {
        return array_diff($roles, $this->getRoles());
    }

    public function sendResetPasswordEmail(User $user, AbstractPasswordToken $passwordToken, string $frontLanguage = 'en'): void
    {
        $subject = sprintf("Password reset for %s %s", $user->getFirstName(), $user->getLastName());
        #todo: traduire les mails
        $this->emailSender->sendTemplateEmail(
            'admin@example.com', #todo: update with configuration
            $user->getEmail(),
            $subject,
            '@GallyBundle/emails/user_reset_password.html.twig',
            [
                'subject' => $subject,
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'password_token' => $passwordToken->getToken(),
                'token_url' => $this->baseUrlProvider->getFrontUrlWithLanguage($frontLanguage) . 'reset-password?' . http_build_query(['token' => $passwordToken->getToken()]),
            ]
        );
    }
}
