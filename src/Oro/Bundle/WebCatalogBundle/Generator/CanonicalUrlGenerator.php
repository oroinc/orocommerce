<?php

namespace Oro\Bundle\WebCatalogBundle\Generator;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator as BaseGenerator;
use Oro\Bundle\WebCatalogBundle\Provider\ContentNodeProvider;
use Oro\Component\Website\WebsiteInterface;

/**
 * Generate Canonical URL based on top level node URL from web catalog having variant for a given entity.
 */
class CanonicalUrlGenerator extends BaseGenerator implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    /**
     * @var ContentNodeProvider
     */
    private $contentNodeProvider;

    public function setContentNodeProvider(ContentNodeProvider $contentNodeProvider): void
    {
        $this->contentNodeProvider = $contentNodeProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getUrl(
        SluggableInterface $entity,
        Localization $localization = null,
        WebsiteInterface $website = null
    ) {
        $url = '';

        // Fetch web catalog data when web_catalog_based_canonical_urls feature is enabled
        if ($this->isFeaturesEnabled()) {
            $variant = $this->contentNodeProvider->getFirstMatchingVariantForEntity($entity, $website);
            if ($variant) {
                $url = $this->getDirectUrl($variant, $localization, $website);
            }
        }
        if (!$url) {
            $url = parent::getUrl($entity, $localization, $website);
        }

        return $url;
    }
}
