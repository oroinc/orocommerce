<?php

declare(strict_types=1);

namespace Oro\Bundle\RedirectBundle\Generator;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Oro\Component\Website\WebsiteInterface;

/**
 * Generates Canonical URLs for a set of entities of the same class in a number of queries
 * that does not depend on the number of entities.
 *
 * The web catalog based canonical URLs are not resolved here, a decorator adds them,
 * the same way as it is done for a single entity.
 *
 * @see \Oro\Bundle\WebCatalogBundle\Generator\BatchCanonicalUrlGenerator
 */
class BatchCanonicalUrlGenerator implements BatchCanonicalUrlGeneratorInterface
{
    public function __construct(
        private readonly CanonicalUrlGenerator $canonicalUrlGenerator,
        private readonly DoctrineHelper $doctrineHelper
    ) {
    }

    /**
     * The identifiers that have no slug are resolved through a Doctrine reference, which is registered
     * in the entity manager even when such an entity does not exist.
     */
    #[\Override]
    public function getUrls(
        string $entityClass,
        array $ids,
        ?Localization $localization = null,
        ?WebsiteInterface $website = null
    ): array {
        if (!$ids) {
            return [];
        }

        $directUrls = [];
        if ($this->canonicalUrlGenerator->isDirectUrlEnabled($website)) {
            $directUrls = $this->getDirectUrls($entityClass, $ids, $localization, $website);
        }

        $urls = [];
        foreach ($ids as $id) {
            $url = $directUrls[$id] ?? '';
            if (!$url) {
                // the routing information providers read only the identifier, the reference stays uninitialized
                $url = $this->canonicalUrlGenerator->getSystemUrl(
                    $this->doctrineHelper->getEntityReference($entityClass, $id),
                    $website
                );
            }
            $urls[$id] = $url;
        }

        return $urls;
    }

    #[\Override]
    public function getDirectUrls(
        string $entityClass,
        array $ids,
        ?Localization $localization = null,
        ?WebsiteInterface $website = null
    ): array {
        if (!$ids) {
            return [];
        }

        $urls = [];
        $slugUrls = $this->loadSlugUrls(
            $entityClass,
            $ids,
            $localization ?? $this->canonicalUrlGenerator->getLocalization()
        );
        foreach ($slugUrls as $id => $slugUrl) {
            $urls[$id] = $this->canonicalUrlGenerator->getAbsoluteUrl($slugUrl, $website);
        }

        return $urls;
    }

    /**
     * @param int[] $ids
     *
     * @return array [entity id => slug url, ...]
     */
    private function loadSlugUrls(string $entityClass, array $ids, ?Localization $localization): array
    {
        $rows = $this->getQueryForLoadSlugUrls($entityClass, $ids, $localization)->getQuery()->getArrayResult();

        $localizationId = $localization?->getId();
        $urls = [];
        $baseUrls = [];
        foreach ($rows as $row) {
            $ownerId = $row['ownerId'];
            // the rows are ordered, so the first slug wins
            if (null === $row['localizationId']) {
                $baseUrls[$ownerId] ??= $row['url'];
            } elseif ((int)$row['localizationId'] === $localizationId) {
                $urls[$ownerId] ??= $row['url'];
            }
        }

        // a slug of the given localization has priority over the base slug
        // @see \Oro\Bundle\RedirectBundle\Entity\SlugAwareTrait::getSlugByLocalization
        return $urls + $baseUrls;
    }

    private function getQueryForLoadSlugUrls(
        string $entityClass,
        array $ids,
        ?Localization $localization
    ): QueryBuilder {
        $qb = $this->doctrineHelper
            ->createQueryBuilder($entityClass, 'owner')
            ->select(QueryBuilderUtil::sprintf(
                's.url, IDENTITY(s.localization) AS localizationId, owner.%s AS ownerId',
                $this->doctrineHelper->getSingleEntityIdentifierFieldName($entityClass)
            ))
            ->innerJoin('owner.slugs', 's')
            ->orderBy('s.id', 'ASC')
            ->setParameter('ids', $ids, ArrayParameterType::INTEGER);

        if (null === $localization) {
            $qb->where('owner IN (:ids) AND s.localization IS NULL');
        } else {
            $qb
                ->where('owner IN (:ids) AND (s.localization = :localization OR s.localization IS NULL)')
                ->setParameter('localization', $localization->getId(), Types::INTEGER);
        }

        return $qb;
    }
}
