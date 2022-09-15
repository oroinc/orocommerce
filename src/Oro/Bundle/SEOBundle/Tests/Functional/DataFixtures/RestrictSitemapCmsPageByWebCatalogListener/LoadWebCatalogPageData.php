<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\DataFixtures\RestrictSitemapCmsPageByWebCatalogListener;

use Oro\Bundle\CMSBundle\ContentVariantType\CmsPageContentVariantType;

class LoadWebCatalogPageData extends AbstractLoadWebCatalogData
{
    protected array $nodesConfigs = [
        [
            'nodeScopes' => [LoadScopeData::SCOPE_DEFAULT],
            'pagesPerScope' => [
                LoadPageData::PAGE1_WEB_CATALOG_SCOPE_DEFAULT => LoadScopeData::SCOPE_DEFAULT,
                LoadPageData::PAGE_WEB_CATALOG_SCOPE_CUSTOMER1 => LoadScopeData::SCOPE_CUSTOMER1,
                LoadPageData::PAGE_WEB_CATALOG_SCOPE_CUSTOMER_GROUP_ANONYMOUS =>
                    LoadScopeData::SCOPE_CUSTOMER_GROUP_ANONYMOUS,
                LoadPageData::PAGE_WEB_CATALOG_SCOPE_CUSTOMER_GROUP1 => LoadScopeData::SCOPE_CUSTOMER_GROUP1,
                LoadPageData::PAGE_WEB_CATALOG_SCOPE_LOCALIZATION_EN_CA => LoadScopeData::SCOPE_LOCALIZATION_EN_CA,
            ]
        ],
        [
            'nodeScopes' => [LoadScopeData::SCOPE_CUSTOMER1],
            'pagesPerScope' => [
                LoadPageData::PAGE2_WEB_CATALOG_SCOPE_DEFAULT => LoadScopeData::SCOPE_CUSTOMER1
            ]
        ],
        [
            'nodeScopes' => [LoadScopeData::SCOPE_CUSTOMER_GROUP_ANONYMOUS],
            'pagesPerScope' => [
                LoadPageData::PAGE3_WEB_CATALOG_SCOPE_DEFAULT => LoadScopeData::SCOPE_CUSTOMER_GROUP_ANONYMOUS
            ]
        ],
        [
            'nodeScopes' => [LoadScopeData::SCOPE_CUSTOMER_GROUP1],
            'pagesPerScope' => [
                LoadPageData::PAGE4_WEB_CATALOG_SCOPE_DEFAULT => LoadScopeData::SCOPE_CUSTOMER_GROUP1
            ]
        ],
        [
            'nodeScopes' => [LoadScopeData::SCOPE_LOCALIZATION_EN_CA],
            'pagesPerScope' => [
                LoadPageData::PAGE5_WEB_CATALOG_SCOPE_DEFAULT => LoadScopeData::SCOPE_LOCALIZATION_EN_CA
            ]
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadPageData::class,
            LoadWebCatalogData::class,
            LoadScopeData::class,
            LoadWebsiteData::class
        ];
    }

    protected function getRoute(): string
    {
        return 'oro_cms_frontend_page_view';
    }

    protected function getContentVariantType(): string
    {
        return CmsPageContentVariantType::TYPE;
    }

    protected function getEntitySetterMethod(): string
    {
        return 'setCmsPage';
    }
}
