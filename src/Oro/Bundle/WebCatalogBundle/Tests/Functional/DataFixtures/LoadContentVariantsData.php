<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\SystemPageContentVariantType;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;

class LoadContentVariantsData extends AbstractFixture implements DependentFixtureInterface
{
    const CUSTOMER_VARIANT = 'web_catalog.content_variant.customer';
    const ROOT_VARIANT = 'web_catalog.content_variant.root';
    const CONTENT_VARIANT_SUBNODE_1 = 'web_catalog.content_variant.subnode_1';
    const CONTENT_VARIANT_SUBNODE_2 = 'web_catalog.content_variant.subnode_2';

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
        $firstCatalogNode->addContentVariant($firstContentVariant);

        $this->setReference(self::CUSTOMER_VARIANT, $firstContentVariant);

        $subNode1 = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1);
        $contentVariant = new ContentVariant();
        $contentVariant->setType(SystemPageContentVariantType::TYPE);
        $contentVariant->setSystemPageRoute('oro_customer_frontend_account_user_index');
        $subNode1->addContentVariant($contentVariant);

        $manager->persist($subNode1);
        $this->setReference(self::CONTENT_VARIANT_SUBNODE_1, $contentVariant);

        $subNode2 = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_2);
        $contentVariant = new ContentVariant();
        $contentVariant->setType(SystemPageContentVariantType::TYPE);
        $contentVariant->setSystemPageRoute('oro_customer_frontend_account_user_index');
        $subNode2->addContentVariant($contentVariant);

        $manager->persist($subNode2);
        $this->setReference(self::CONTENT_VARIANT_SUBNODE_2, $contentVariant);

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
