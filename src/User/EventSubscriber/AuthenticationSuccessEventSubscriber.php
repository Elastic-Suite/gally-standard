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
use CoopTilleuls\ForgotPasswordBundle\Provider\ProviderInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use CoopTilleuls\ForgotPasswordBundle\Event\CreateTokenEvent;
use Gally\Category\Service\CategoryProductPositionManager;
use Gally\Configuration\Service\BaseUrlProvider;
use Gally\Email\Service\EmailSender;
use Gally\User\Entity\User;
use Gally\User\Service\UserManager;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class AuthenticationSuccessEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            Events::AUTHENTICATION_SUCCESS => 'isUserActive',
        ];
    }

    public function isUserActive(AuthenticationSuccessEvent $event): void
    {
        /** @var User $user */
        $user = $event->getUser();
        if (!$user?->getIsActive()) {
            throw new UserNotFoundException(sprintf('The user "%s" is not active.', $user?->getEmail()));
        }
    }
}
