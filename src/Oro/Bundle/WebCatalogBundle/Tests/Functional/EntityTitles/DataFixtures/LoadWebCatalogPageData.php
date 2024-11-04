<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\EntityTitles\DataFixtures;

use Oro\Bundle\CMSBundle\ContentVariantType\CmsPageContentVariantType;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\ScopeBundle\Tests\Functional\DataFixtures\LoadScopeData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;

class LoadWebCatalogPageData extends AbstractLoadWebCatalogData
{
    #[\Override]
    public function getDependencies()
    {
        return [
            LoadPageData::class,
            LoadWebCatalogData::class,
            LoadScopeData::class
        ];
    }

    #[\Override]
    protected function getRoute()
    {
        return 'oro_cms_frontend_page_view';
    }

    #[\Override]
    protected function getContentVariantType()
    {
        return CmsPageContentVariantType::TYPE;
    }

    #[\Override]
    protected function getEntitySetterMethod()
    {
        return 'setCmsPage';
    }

    #[\Override]
    protected function getEntity()
    {
        return $this->getReference(LoadPageData::PAGE_1);
    }
}
