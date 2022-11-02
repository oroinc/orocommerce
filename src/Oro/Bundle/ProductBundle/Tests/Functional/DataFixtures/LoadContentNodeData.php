<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\FrontendTestFrameworkBundle\Entity\TestContentNode;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;

class LoadContentNodeData extends AbstractFixture implements DependentFixtureInterface
{
    const FIRST_CONTENT_NODE = 'firstContentNode';
    const SECOND_CONTENT_NODE = 'secondContentNode';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadWebCatalogsData::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $firstContentNode = new TestContentNode();
        $firstContentNode->setWebCatalog($this->getReference(LoadWebCatalogsData::FIRST_WEB_CATALOG));
        $manager->persist($firstContentNode);
        $this->setReference(self::FIRST_CONTENT_NODE, $firstContentNode);

        $secondContentNode = new TestContentNode();
        $secondContentNode->setWebCatalog($this->getReference(LoadWebCatalogsData::SECOND_WEB_CATALOG));
        $manager->persist($secondContentNode);
        $this->setReference(self::SECOND_CONTENT_NODE, $secondContentNode);

        $manager->flush();
    }
}
