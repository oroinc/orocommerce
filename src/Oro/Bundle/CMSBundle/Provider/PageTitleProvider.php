<?php

namespace Oro\Bundle\CMSBundle\Provider;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Component\WebCatalog\ContentVariantTitleProviderInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class PageTitleProvider implements ContentVariantTitleProviderInterface
{
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
        if ((string)$contentVariant->getType() !== 'landing_page_cms_page'
            || null === $this->propertyAccessor->getValue($contentVariant, 'landingPageCMSPage')
        ) {
            return null;
        }

        $page  = $this->propertyAccessor->getValue($contentVariant, 'landingPageCMSPage');
        $title = null;
        if ($page instanceof Page && $page->getTitle()) {
            $title = $page->getTitle();
        }

        return $title;
    }
}
