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

namespace Gally\Migrations\Trait;

trait TrackingEventSourceFieldPersistorTrait
{
    use EntitySourceFieldPersistorTrait;

    public function getTrackingEventMetadataId(): int
    {
        return $this->resolveSourceFieldMetadataId('tracking_event');
    }

    public function addInsertTrackingEventSourceFieldSql(string $code, string $type): void
    {
        $this->insertSourceFieldSql('tracking_event', $code, $type);
    }

    public function addUpdateTrackingEventSourceFieldSql(string $code, array $valuesToUpdate): void
    {
        $this->updateSourceFieldSql('tracking_event', $code, $valuesToUpdate);
    }
}
