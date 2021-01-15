<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\ContentVariantType\CmsPageContentVariantType;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentNodesData;

class LoadContentVariantData extends AbstractFixture implements DependentFixtureInterface
{
    public const VARIANT = 'cms.content_variant';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var ContentNode $firstCatalogNode */
        $firstCatalogNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT);

        $firstContentVariant = new ContentVariant();
        $firstContentVariant->setType(CmsPageContentVariantType::TYPE);
        $firstContentVariant->setCmsPage($this->getReference(LoadPageData::PAGE_1));
        $firstCatalogNode->addContentVariant($firstContentVariant);

        $this->setReference(self::VARIANT, $firstContentVariant);

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadContentNodesData::class,
            LoadPageData::class
        ];
    }
}
