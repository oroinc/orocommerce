<?php

namespace Oro\Bundle\SEOBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadBrandDemoData;

/**
 * Loads SEO localized fields for brands.
 */
class LoadBrandDemoMetaData extends AbstractFixture implements DependentFixtureInterface
{
    use LoadDemoMetaDataTrait;

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadBrandDemoData::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->addMetaFieldsData($manager, $manager->getRepository(Brand::class)->findAll());
        $manager->flush();
    }
}
