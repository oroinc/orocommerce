<?php

namespace Oro\Bundle\CMSBundle\Provider;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Component\WebCatalog\ContentVariantTitleProviderInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;

class PageTitleProvider implements ContentVariantTitleProviderInterface
{
    /**
     * @inheritdoc
     */
    public function getTitle(ContentVariantInterface $contentVariant)
    {
        if ($contentVariant->getType() != 'landing_page_cms_page') {
            return null;
        }

        $page  = $contentVariant->getLandingPageCMSPage();
        $title = null;
        if ($page instanceof Page) {
            if ($page->getTitle()) {
                $title = $page->getTitle();
            }
        }

        return $title;
    }
}
