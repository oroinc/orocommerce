<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\DataFixtures\RestrictSitemapCmsPageByWebCatalogListener;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

class LoadWebCatalogData extends AbstractFixture
{
    public const CATALOG_1 = 'web_catalog.1';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $catalog = new WebCatalog();
        $catalog->setName(self::CATALOG_1);
        $catalog->setDescription(self::CATALOG_1 . ' description');

        $organization = $manager->getRepository(Organization::class)->findOneBy([]);
        $catalog->setOrganization($organization);
        $manager->persist($catalog);
        $this->setReference(self::CATALOG_1, $catalog);

        $manager->flush();
    }
}
