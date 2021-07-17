<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class LoadContentBlockData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            LoadOrganization::class,
            LoadBusinessUnit::class
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $manager->persist($this->createContentBlock(true, 'content_block_1'));
        $manager->persist($this->createContentBlock(false, 'content_block_2'));

        $manager->flush();
    }

    protected function createContentBlock(bool $enabled, string $alias): ContentBlock
    {
        $contentBlock = new ContentBlock();
        $contentBlock->setEnabled($enabled);
        $contentBlock->setAlias($alias);
        $contentBlock->setDefaultTitle($alias);
        $contentBlock->setOwner($this->getReference('business_unit'));
        $contentBlock->setOrganization($this->getReference('organization'));
        $this->addReference($contentBlock->getAlias(), $contentBlock);

        return $contentBlock;
    }
}
