<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

class LoadWebCatalogData extends AbstractFixture
{
    const CATALOG_1 = 'web_catalog.1';
    const CATALOG_2 = 'web_catalog.2';
    const CATALOG_3 = 'web_catalog.3';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ([self::CATALOG_1, self::CATALOG_2, self::CATALOG_3] as $catalogReference) {
            $catalog = $this->createCatalog($catalogReference);
            $organization = $manager->getRepository('OroOrganizationBundle:Organization')->findOneBy([]);
            $catalog->setOrganization($organization);
            $manager->persist($catalog);
            $this->setReference($catalogReference, $catalog);
        }

        $manager->flush();
    }

    /**
     * @param string $catalogName
     * @return WebCatalog
     */
    private function createCatalog($catalogName)
    {
        $catalog = new WebCatalog();
        $catalog->setName($catalogName);
        $catalog->setDescription($catalogName . ' description');

        return $catalog;
    }
}
