<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\SystemPageContentVariantType;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;

class LoadContentVariantsData extends AbstractFixture implements DependentFixtureInterface
{
    const CUSTOMER_VARIANT = 'web_catalog.content_variant.customer';
    const ROOT_VARIANT = 'web_catalog.content_variant.root';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var ContentNode $firstCatalogNode */
        $firstCatalogNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT);

        $firstContentVariant = new ContentVariant();
        $firstContentVariant->setType(SystemPageContentVariantType::TYPE);
        $firstContentVariant->setSystemPageRoute('oro_customer_frontend_customer_user_index');
        $firstContentVariant->setNode($firstCatalogNode);

        $this->setReference(self::CUSTOMER_VARIANT, $firstContentVariant);
        $manager->persist($firstContentVariant);

        /** @var ContentNode $secondCatalogNode */
        $secondCatalogNode = $this->getReference(LoadContentNodesData::CATALOG_2_ROOT);

        $secondContentVariant = new ContentVariant();
        $secondContentVariant->setType(SystemPageContentVariantType::TYPE);
        $secondContentVariant->setSystemPageRoute('oro_frontend_root');
        $secondContentVariant->setNode($secondCatalogNode);

        $this->setReference(self::ROOT_VARIANT, $secondContentVariant);
        $manager->persist($secondContentVariant);

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadContentNodesData::class];
    }
}
