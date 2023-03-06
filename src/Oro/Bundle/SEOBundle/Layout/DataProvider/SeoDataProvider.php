<?php

namespace Oro\Bundle\SEOBundle\Layout\DataProvider;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\WebCatalogBundle\Provider\RequestWebContentVariantProvider;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * The provider for SEO data.
 */
class SeoDataProvider
{
    /** @var LocalizationHelper */
    private $localizationHelper;

    /** @var RequestWebContentVariantProvider */
    private $requestWebContentVariantProvider;

    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    public function __construct(
        LocalizationHelper $localizationHelper,
        RequestWebContentVariantProvider $requestWebContentVariantProvider,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->localizationHelper = $localizationHelper;
        $this->requestWebContentVariantProvider = $requestWebContentVariantProvider;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param string $metaField
     *
     * @return LocalizedFallbackValue|null
     */
    public function getMetaInformationFromContentNode($metaField)
    {
        $node = $this->getContentNode();
        $value = null;
        if ($node) {
            $value = $this->getLocalizedMetaValue($node, $metaField);
        }
        return $value;
    }

    /**
     * @param object $data
     * @param string $metaField
     *
     * @return LocalizedFallbackValue|null
     */
    public function getMetaInformation($data, $metaField)
    {
        $value = $this->getMetaInformationFromContentNode($metaField);

        $valueAsString = (string)$value;
        if ($valueAsString === null || $valueAsString === '') {
            $value = $this->getLocalizedMetaValue($data, $metaField);
        }

        return $value;
    }

    /**
     * @return ContentNodeInterface|null
     */
    protected function getContentNode()
    {
        $contentVariant = $this->requestWebContentVariantProvider->getContentVariant();

        return null !== $contentVariant
            ? $contentVariant->getNode()
            : null;
    }

    /**
     * @param object $data
     * @param string $metaField
     *
     * @return LocalizedFallbackValue|null
     */
    protected function getLocalizedMetaValue($data, $metaField)
    {
        $value = null;
        $metaData = $this->propertyAccessor->getValue($data, $metaField);
        if ($metaData instanceof Collection) {
            $value = $this->localizationHelper->getLocalizedValue($metaData);
        }

        return $value;
    }
}
