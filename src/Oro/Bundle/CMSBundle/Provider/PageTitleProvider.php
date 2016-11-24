<?php

namespace Oro\Bundle\CMSBundle\Provider;

use Oro\Bundle\CMSBundle\ContentVariantType\CmsPageContentVariantType;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Component\DependencyInjection\ServiceLink;
use Oro\Component\WebCatalog\ContentVariantTitleProviderInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class PageTitleProvider implements ContentVariantTitleProviderInterface
{
    const FIELD_NAME = 'landingPageCMSPage';

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @var ServiceLink
     */
    protected $localizationHelperLink;

    /**
     * @param PropertyAccessor $propertyAccessor
     * @param ServiceLink $localizationHelperLink
     */
    public function __construct(PropertyAccessor $propertyAccessor, ServiceLink $localizationHelperLink)
    {
        $this->propertyAccessor = $propertyAccessor;
        $this->localizationHelperLink = $localizationHelperLink;
    }

    /**
     * @return LocalizationHelper|object
     */
    protected function getLocalizationHelper()
    {
        return $this->localizationHelperLink->getService();
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle(ContentVariantInterface $contentVariant)
    {
        if ($contentVariant->getType() !== CmsPageContentVariantType::TYPE) {
            return null;
        }

        $page  = $this->propertyAccessor->getValue($contentVariant, self::FIELD_NAME);
        if ($page instanceof Page) {
            return $this->getLocalizationHelper()->getFirstNonEmptyLocalizedValue($page->getTitles());
        }

        return null;
    }
}
