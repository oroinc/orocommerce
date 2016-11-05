<?php

namespace Oro\Bundle\CMSBundle\Provider;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Component\WebCatalog\ContentVariantTitleProviderInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class PageTitleProvider implements ContentVariantTitleProviderInterface
{
    const SUPPORTED_TYPE ='landing_page_cms_page';
    const FIELD_NAME = 'landingPageCMSPage';

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @param PropertyAccessor $propertyAccessor
     */
    public function __construct(PropertyAccessor $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle(ContentVariantInterface $contentVariant)
    {
        if ($contentVariant->getType() !== self::SUPPORTED_TYPE) {
            return null;
        }

        $page  = $this->propertyAccessor->getValue($contentVariant, self::FIELD_NAME);
        if ($page instanceof Page && $page->getDefaultTitle() instanceof LocalizedFallbackValue) {
            return $page->getDefaultTitle()->getText();
        }

        return null;
    }
}
