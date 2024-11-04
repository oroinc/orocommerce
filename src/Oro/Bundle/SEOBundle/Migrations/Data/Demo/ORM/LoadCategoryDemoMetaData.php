<?php

namespace Oro\Bundle\SEOBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Migrations\Data\Demo\ORM\LoadCategoryDemoData;

/**
 * Loads SEO localized fields for categories.
 */
class LoadCategoryDemoMetaData extends AbstractFixture implements DependentFixtureInterface
{
    use LoadDemoMetaDataTrait;

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadCategoryDemoData::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->addMetaFieldsData($manager, $manager->getRepository(Category::class)->findAll());
        $manager->flush();
    }
}
