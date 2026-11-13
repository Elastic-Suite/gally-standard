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

namespace Gally\Index\Tests\EventSubscriber;

use Gally\Index\Event\BeforeBulkRequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TestDocumentModifier implements EventSubscriberInterface
{
    public const DOCUMENT_NAME_TO_MODIFY = 'Document to modify';
    public const DOCUMENT_NAME_TO_REMOVE = 'Document to remove';
    public const MODIFIED_SUFFIX = ' (modified)';

    public static function getSubscribedEvents(): array
    {
        return [BeforeBulkRequestEvent::NAME => 'onBeforeBulkRequest'];
    }

    public function onBeforeBulkRequest(BeforeBulkRequestEvent $event): void
    {
        $request = $event->getRequest();
        foreach ($request->getTargets() as $target) {
            $documents = array_filter(
                $request->getDocuments($target),
                fn (array $document): bool => self::DOCUMENT_NAME_TO_REMOVE !== ($document['name'] ?? null)
            );
            $documents = array_map(function (array $document): array {
                if (self::DOCUMENT_NAME_TO_MODIFY === ($document['name'] ?? null)) {
                    $document['name'] .= self::MODIFIED_SUFFIX;
                }

                return $document;
            }, $documents);
            $request->setDocuments($target, $documents);
        }
    }
}
