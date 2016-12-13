<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadSlugsData;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;

class LoadContentVariantSlugsData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var ContentVariant $variant */
        $variant = $this->getReference(LoadContentVariantsData::CUSTOMER_VARIANT);
        /** @var Slug $slug */
        $slug = $this->getReference(LoadSlugsData::SLUG_URL_USER);

        $variant->addSlug($slug);
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadContentVariantsData::class,
            LoadSlugsData::class
        ];
    }
}
