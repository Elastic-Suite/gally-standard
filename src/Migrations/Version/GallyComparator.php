<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @package   Gally
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\Migrations\Version;

use Doctrine\Migrations\Version\Comparator;
use Doctrine\Migrations\Version\Version;

/**
 * Allows to sort migration executions by the date included in the  filename for Gally\* and  DoctrineMigrations\* namespaces.
 */
class GallyComparator implements Comparator
{
    /**
     * Compare two versions of doctrine migrations.
     */
    public function compare(Version $a, Version $b): int
    {
        $a = (string) $a;
        $b = (string) $b;
        if (str_starts_with($a, 'Gally\\') || str_starts_with($b, 'Gally\\') || str_starts_with($a, 'DoctrineMigrations\\') || str_starts_with($b, 'DoctrineMigrations\\')) {
            if ($a === $b) {
                return 0;
            }

            if (!(str_starts_with($a, 'Gally\\') || str_starts_with($a, 'DoctrineMigrations\\'))
                && (str_starts_with($b, 'Gally\\') || str_starts_with($b, 'DoctrineMigrations\\'))
            ) {
                return 1;
            }

            if ((str_starts_with($a, 'Gally\\') || str_starts_with($a, 'DoctrineMigrations\\'))
                && !(str_starts_with($b, 'Gally\\') || str_starts_with($b, 'DoctrineMigrations\\'))
            ) {
                return -1;
            }

            $datesB = $datesA = [];
            $datePattern = '/([0-9]{4})(0[1-9]|1[0-2])(0[1-9]|[1-2][0-9]|3[0-1])(2[0-3]|[01][0-9])([0-5][0-9])([0-5][0-9])/';
            if (preg_match($datePattern, $a, $datesA) && preg_match($datePattern, $b, $datesB)) {
                $a = $datesA[0];
                $b = $datesB[0];
            }
        }

        return strcmp($a, $b);
    }
}
