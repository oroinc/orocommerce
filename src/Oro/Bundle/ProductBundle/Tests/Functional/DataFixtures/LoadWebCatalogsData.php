<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\FrontendTestFrameworkBundle\Entity\TestWebCatalog;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;

class LoadWebCatalogsData extends AbstractFixture
{
    const FIRST_WEB_CATALOG = 'firstWebCatalog';
    const SECOND_WEB_CATALOG = 'secondWebCatalog';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $firstWebCatalog = new TestWebCatalog();
        $manager->persist($firstWebCatalog);
        $this->setReference(self::FIRST_WEB_CATALOG, $firstWebCatalog);

        $secondWebCatalog = new TestWebCatalog();
        $manager->persist($secondWebCatalog);
        $this->setReference(self::SECOND_WEB_CATALOG, $secondWebCatalog);

        $manager->flush();
    }
}
