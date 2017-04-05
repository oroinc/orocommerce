<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\SegmentBundle\Entity\Segment;

class UpdateFeaturedProductsSegmentIdConfig extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadFeaturedProductsSegmentData::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $segment = $manager->getRepository(Segment::class)->findOneBy(['name' => 'Featured Products']);

        $name = sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::FEATURED_PRODUCTS_SEGMENT_ID);

        $configManager = $this->container->get('oro_config.global');
        $configManager->set($name, $segment->getId());

        $configManager->flush();
    }
}
