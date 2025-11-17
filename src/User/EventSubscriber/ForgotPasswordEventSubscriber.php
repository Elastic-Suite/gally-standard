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

namespace Gally\User\EventSubscriber;

use CoopTilleuls\ForgotPasswordBundle\Event\CreateTokenEvent;
use CoopTilleuls\ForgotPasswordBundle\Event\UpdatePasswordEvent;
use Gally\User\Entity\User;
use Gally\User\Service\UserManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ForgotPasswordEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UserManager $userManager,
        private RequestStack $requestStack,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CreateTokenEvent::class => 'sendResetPasswordEmail',
            UpdatePasswordEvent::class => 'updatePassword',
        ];
    }

    public function sendResetPasswordEmail(CreateTokenEvent $event): void
    {
        $passwordToken = $event->getPasswordToken();
        /** @var User $user */
        $user = $passwordToken->getUser();
        $language = json_decode($this->requestStack->getCurrentRequest()->getContent(), true)['language'] ?? 'en';

        $this->userManager->sendResetPasswordEmail($user, $passwordToken, $language);
    }

    public function updatePassword(UpdatePasswordEvent $event): void
    {
        $passwordToken = $event->getPasswordToken();
        /** @var User $user */
        $user = $passwordToken->getUser();
        $this->userManager->update($user->getEmail(), null, null, null, null, $event->getPassword());
    }
}
