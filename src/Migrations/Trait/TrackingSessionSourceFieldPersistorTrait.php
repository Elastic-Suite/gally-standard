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

trait TrackingSessionSourceFieldPersistorTrait
{
    use EntitySourceFieldPersistorTrait;

    public function getTrackingSessionMetadataId(): int
    {
        return $this->resolveSourceFieldMetadataId('tracking_session');
    }

    public function addInsertTrackingSessionSourceFieldSql(string $code, string $type): void
    {
        $this->insertSourceFieldSql('tracking_session', $code, $type);
    }

    public function addUpdateTrackingSessionSourceFieldSql(string $code, array $valuesToUpdate): void
    {
        $this->updateSourceFieldSql('tracking_session', $code, $valuesToUpdate);
    }
}
