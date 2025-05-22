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

use CoopTilleuls\ForgotPasswordBundle\Event\UpdatePasswordEvent;
use Doctrine\ORM\Event\PostPersistEventArgs;
use CoopTilleuls\ForgotPasswordBundle\Event\CreateTokenEvent;
use Gally\Category\Service\CategoryProductPositionManager;
use Gally\Email\Service\EmailSender;
use Gally\User\Entity\User;
use Gally\User\Service\UserManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ForgotPasswordEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EmailSender $emailSender,
        private UserManager $userManager,
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

        #todo: traduire les mails
        $this->emailSender->sendTemplateEmail(
            'admin@example.com', #todo: update with configuration
            $user->getEmail(),
            'Reset password',
            '@GallyBundle/emails/user_reset_password.html.twig',
            ['password_token' => $passwordToken->getToken()]
        );
    }

    public function updatePassword(UpdatePasswordEvent $event): void
    {
        $passwordToken = $event->getPasswordToken();
        /** @var User $user */
        $user = $passwordToken->getUser();
        $this->userManager->update($user->getEmail(), null, null, $event->getPassword());
        //todo : gérer l'echec de validation du password ? envoyer un mail pour dire changement du password fait.
    }
}
