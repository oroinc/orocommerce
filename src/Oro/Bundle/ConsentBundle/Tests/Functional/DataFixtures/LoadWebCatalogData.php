<?php

namespace Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

class LoadWebCatalogData extends AbstractFixture
{
    public const string CATALOG_1_USE_IN_ROUTING = 'web_catalog.1';
    public const string CATALOG_2 = 'web_catalog.2';
    public const string CATALOG_3 = 'web_catalog.3';

    private static array $data = [
        self::CATALOG_1_USE_IN_ROUTING,
        self::CATALOG_2,
        self::CATALOG_3
    ];

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        foreach (self::$data as $catalogReference) {
            $webCatalog = new WebCatalog();
            $webCatalog->setName($catalogReference);
            $webCatalog->setDescription($catalogReference . ' description');
            $manager->persist($webCatalog);
            $this->setReference($catalogReference, $webCatalog);
        }
        $manager->flush();
    }
}
