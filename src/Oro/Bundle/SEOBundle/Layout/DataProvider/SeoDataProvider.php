<?php

namespace Oro\Bundle\SEOBundle\Layout\DataProvider;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Component\WebCatalog\Entity\ContentNodeAwareInterface;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class SeoDataProvider
{
    /**
     * @var LocalizationHelper
     */
    protected $localizationHelper;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @param LocalizationHelper $localizationHelper
     * @param RequestStack $requestStack
     * @param PropertyAccessor $propertyAccessor
     */
    public function __construct(
        LocalizationHelper $localizationHelper,
        RequestStack $requestStack,
        PropertyAccessor $propertyAccessor
    ) {
        $this->localizationHelper = $localizationHelper;
        $this->requestStack = $requestStack;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param object $data
     * @param string $metaField
     * @return null|LocalizedFallbackValue
     */
    public function getMetaInformation($data, $metaField)
    {
        $node = $this->getContentNode();
        $value = null;
        if ($node) {
            $value = $this->getLocalizedMetaValue($node, $metaField);
        }

        $valueAsString = (string)$value;
        if ($valueAsString === null || $valueAsString === '') {
            $value = $this->getLocalizedMetaValue($data, $metaField);
        }

        return $value;
    }

    /**
     * @return null|ContentNodeInterface
     */
    protected function getContentNode()
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request && $request->attributes->has('_content_variant')) {
            $contentVariant = $request->attributes->get('_content_variant');

            if ($contentVariant instanceof ContentNodeAwareInterface) {
                return $contentVariant->getNode();
            }
        }

        return null;
    }

    /**
     * @param object $data
     * @param string $metaField
     * @return LocalizedFallbackValue|null
     */
    protected function getLocalizedMetaValue($data, $metaField)
    {
        $value = null;
        $metaData = $this->propertyAccessor->getValue($data, $metaField);
        if ($metaData instanceof Collection) {
            $value =  $this->localizationHelper->getLocalizedValue($metaData);
        }

        return $value;
    }
}
