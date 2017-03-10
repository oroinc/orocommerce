<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\EntityTitles\DataFixtures;

use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\ScopeBundle\Tests\DataFixtures\LoadScopeData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;

class LoadWebCatalogPageData extends AbstractLoadWebCatalogData
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadPageData::class,
            LoadWebCatalogData::class,
            LoadScopeData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getRoute()
    {
        return 'oro_cms_frontend_page_view';
    }

    /**
     * {@inheritdoc}
     */
    protected function getContentVariantType()
    {
        return 'cms_page';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntitySetterMethod()
    {
        return 'setCmsPage';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntity()
    {
        return $this->getReference(LoadPageData::PAGE_1);
    }
}
