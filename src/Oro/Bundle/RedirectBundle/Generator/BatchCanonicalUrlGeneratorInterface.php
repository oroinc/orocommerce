<?php

declare(strict_types=1);

namespace Oro\Bundle\RedirectBundle\Generator;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Component\Website\WebsiteInterface;

/**
 * Generates Canonical URLs for a set of entities of the same class in a number of queries
 * that does not depend on the number of entities.
 */
interface BatchCanonicalUrlGeneratorInterface
{
    /**
     * @param int[] $ids
     *
     * @return array [entity id => canonical url, ...]
     */
    public function getUrls(
        string $entityClass,
        array $ids,
        ?Localization $localization = null,
        ?WebsiteInterface $website = null
    ): array;

    /**
     * @param int[] $ids
     *
     * @return array [entity id => canonical url, ...] the entities that do not have a slug are not returned
     */
    public function getDirectUrls(
        string $entityClass,
        array $ids,
        ?Localization $localization = null,
        ?WebsiteInterface $website = null
    ): array;
}
