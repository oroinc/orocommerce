<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\DataFixtures\RestrictSitemapCmsPageByWebCatalogListener;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

class LoadPageData extends AbstractFixture
{
    public const PAGE1_WEB_CATALOG_SCOPE_DEFAULT = 'page1.web_catalog.scope_default';
    public const PAGE2_WEB_CATALOG_SCOPE_DEFAULT = 'page2.web_catalog.scope_default';
    public const PAGE3_WEB_CATALOG_SCOPE_DEFAULT = 'page3.web_catalog.scope_default';
    public const PAGE4_WEB_CATALOG_SCOPE_DEFAULT = 'page4.web_catalog.scope_default';
    public const PAGE5_WEB_CATALOG_SCOPE_DEFAULT = 'page5.web_catalog.scope_default';
    public const PAGE_OUT_OF_WEB_CATALOG = 'page.out_of_web_catalog';
    public const PAGE_WEB_CATALOG_SCOPE_LOCALIZATION_EN_CA = 'page.web_catalog.scope_localization_en_ca';
    public const PAGE_WEB_CATALOG_SCOPE_CUSTOMER_GROUP_ANONYMOUS = 'page.web_catalog.scope_cg_anonymous';
    public const PAGE_WEB_CATALOG_SCOPE_CUSTOMER_GROUP1 = 'page.web_catalog.scope_cg1';
    public const PAGE_WEB_CATALOG_SCOPE_CUSTOMER1 = 'page.web_catalog.scope_customer1';

    protected static array $page = [
        self::PAGE1_WEB_CATALOG_SCOPE_DEFAULT => [],
        self::PAGE2_WEB_CATALOG_SCOPE_DEFAULT => [],
        self::PAGE3_WEB_CATALOG_SCOPE_DEFAULT => [],
        self::PAGE4_WEB_CATALOG_SCOPE_DEFAULT => [],
        self::PAGE5_WEB_CATALOG_SCOPE_DEFAULT => [],
        self::PAGE_OUT_OF_WEB_CATALOG => [],
        self::PAGE_WEB_CATALOG_SCOPE_LOCALIZATION_EN_CA => [],
        self::PAGE_WEB_CATALOG_SCOPE_CUSTOMER_GROUP_ANONYMOUS => [],
        self::PAGE_WEB_CATALOG_SCOPE_CUSTOMER_GROUP1 => [],
        self::PAGE_WEB_CATALOG_SCOPE_CUSTOMER1 => [],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $manager->createQuery('DELETE OroCMSBundle:Page')->execute(); // remove all built-in pages before tests
        foreach (self::$page as $menuItemReference => $data) {
            $entity = new Page();
            $entity->addTitle((new LocalizedFallbackValue())->setString($menuItemReference));
            $entity->setContent($menuItemReference);
            $this->setReference($menuItemReference, $entity);
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
