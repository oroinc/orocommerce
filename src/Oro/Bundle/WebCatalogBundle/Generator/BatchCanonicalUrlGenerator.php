<?php

declare(strict_types=1);

namespace Oro\Bundle\WebCatalogBundle\Generator;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\RedirectBundle\Generator\BatchCanonicalUrlGeneratorInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Provider\ContentNodeProvider;
use Oro\Component\Website\WebsiteInterface;

/**
 * Generates Canonical URLs for a set of entities based on the top level node URL
 * from the web catalog having a variant for a given entity.
 *
 * The entities without a usable variant are delegated to the decorated generator.
 */
class BatchCanonicalUrlGenerator implements BatchCanonicalUrlGeneratorInterface, FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    public function __construct(
        private readonly BatchCanonicalUrlGeneratorInterface $innerGenerator,
        private readonly ContentNodeProvider $contentNodeProvider
    ) {
    }

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

        $variantUrls = $this->getVariantUrls($entityClass, $ids, $localization, $website);
        $idsWithoutVariant = array_values(array_diff($ids, array_keys($variantUrls)));
        $innerUrls = $idsWithoutVariant
            ? $this->innerGenerator->getUrls($entityClass, $idsWithoutVariant, $localization, $website)
            : [];

        // the order of the returned URLs follows the order of the requested identifiers
        $urls = [];
        foreach ($ids as $id) {
            $urls[$id] = $variantUrls[$id] ?? $innerUrls[$id];
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
        return $this->innerGenerator->getDirectUrls($entityClass, $ids, $localization, $website);
    }

    /**
     * @param int[] $ids
     *
     * @return array [entity id => canonical url, ...] only the entities with a usable variant are returned
     */
    private function getVariantUrls(
        string $entityClass,
        array $ids,
        ?Localization $localization,
        ?WebsiteInterface $website
    ): array {
        // the whole path is behind the web_catalog_based_canonical_urls feature
        if (!$this->isFeaturesEnabled()) {
            return [];
        }

        $variantIds = $this->contentNodeProvider->getFirstMatchingVariantIdsForEntities($entityClass, $ids, $website);
        if (!$variantIds) {
            return [];
        }

        // a content variant has no routing information provider, so only its slug based URL can be resolved
        $variantUrls = $this->innerGenerator->getDirectUrls(
            ContentVariant::class,
            array_values($variantIds),
            $localization,
            $website
        );

        $urls = [];
        foreach ($variantIds as $ownerId => $variantId) {
            if (!empty($variantUrls[$variantId])) {
                $urls[$ownerId] = $variantUrls[$variantId];
            }
        }

        return $urls;
    }
}
