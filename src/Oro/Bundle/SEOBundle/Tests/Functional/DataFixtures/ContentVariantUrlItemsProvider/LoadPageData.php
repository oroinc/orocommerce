<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\DataFixtures\ContentVariantUrlItemsProvider;

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

    protected static array $page = [
        self::PAGE1_WEB_CATALOG_SCOPE_DEFAULT => [],
        self::PAGE2_WEB_CATALOG_SCOPE_DEFAULT => [],
        self::PAGE3_WEB_CATALOG_SCOPE_DEFAULT => [],
        self::PAGE4_WEB_CATALOG_SCOPE_DEFAULT => [],
        self::PAGE5_WEB_CATALOG_SCOPE_DEFAULT => [],
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
