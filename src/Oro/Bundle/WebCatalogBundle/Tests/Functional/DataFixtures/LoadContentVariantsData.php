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

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var ContentNode $node */
        $node = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT);

        $contentVariant = new ContentVariant();
        $contentVariant->setType(SystemPageContentVariantType::TYPE);
        $contentVariant->setSystemPageRoute('oro_customer_frontend_account_user_index');
        $contentVariant->setNode($node);

        $this->setReference(self::CUSTOMER_VARIANT, $contentVariant);

        $manager->persist($contentVariant);
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
