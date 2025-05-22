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

namespace Gally\User\Controller;

use Symfony\Component\HttpFoundation\Response;

final class GetTokenController
{
    public function __invoke(): Response
    {
        /* By default, the user's data is returned, which may include sensitive information. That's why we clear the response content.
         * Original controller @see \CoopTilleuls\ForgotPasswordBundle\Controller\GetToken::__invoke */
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
