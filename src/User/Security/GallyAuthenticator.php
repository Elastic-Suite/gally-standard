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

namespace Gally\User\Security;

use Gally\User\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authenticator\JWTAuthenticator;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class GallyAuthenticator extends JWTAuthenticator
{
    protected function loadUser(array $payload, string $identity): UserInterface
    {
        /** @var User $user */
        $user = parent::loadUser($payload, $identity);
        if (!$user->getIsActive()) {
            throw new UserNotFoundException(\sprintf('The user "%s" is not active.', $identity));
        }

        return $user;
    }
}
