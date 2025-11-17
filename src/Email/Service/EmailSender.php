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

namespace Gally\Email\Service;

use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailSender
{
    public const EMAIL_SEPARATOR = ';';
    public const EMAIL_RESEND_ATTEMPTS = 3;

    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
    ) {
    }

    public function sendTemplateEmail(
        string $from,
        string $to,
        string $subject,
        string $template,
        array $context = [],
        ?string $cc = null,
        ?string $replyTo = null,
        ?string $bcc = null,
    ): void {
        $email = (new TemplatedEmail())
            ->subject($subject)
            ->htmlTemplate($template)
            ->context($context);

        $functions = ['from' => $from, 'to' => $to, 'cc' => $cc, 'replyTo' => $replyTo, 'bcc' => $bcc];

        foreach ($functions as $function => $addresses) {
            if (null !== $addresses) {
                $addresses = explode(self::EMAIL_SEPARATOR, $addresses);
                $emailList = [];
                foreach ($addresses as $address) {
                    $emailList[] = $this->getEmailObject($address);
                }

                $email->{$function}(...$emailList);
            }
        }

        for ($resend = 0; $resend < self::EMAIL_RESEND_ATTEMPTS; ++$resend) {
            try {
                $this->mailer->send($email);
                break;
            } catch (TransportExceptionInterface $e) {
                $this->logger->error(
                    '[EMAIL] Sending failure',
                    [
                        'attempt' => ($resend + 1),
                        'from' => $from,
                        'to' => $to,
                        'template' => $template,
                        'error' => $e->getMessage(),
                        'debug' => $e->getDebug(),
                    ]
                );
            }
        }
    }

    public function getEmailObject(string $email, ?string $name = null): Address
    {
        if (false !== stripos($email, '<')) {
            $emailObject = Address::create($email);
        } elseif (null !== $name) {
            $emailObject = new Address($email, $name);
        } else {
            $emailObject = new Address($email);
        }

        return $emailObject;
    }
}
