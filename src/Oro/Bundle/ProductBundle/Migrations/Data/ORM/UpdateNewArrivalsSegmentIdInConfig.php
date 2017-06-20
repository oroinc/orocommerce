<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\GlobalScopeManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UpdateNewArrivalsSegmentIdInConfig extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{

    /**
     * @var GlobalScopeManager
     */
    private $configManager;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->configManager = $container->get('oro_config.global');
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            LoadNewArrivalProductsSegmentData::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $segment = $this->findSegment($manager);

        if (!$segment) {
            return;
        }

        $key = $this->getSegmentIdOptionKey();

        $this->configManager->set($key, $segment->getId());

        $this->configManager->flush();
    }

    /**
     * @param ObjectManager $manager
     *
     * @return null|object|Segment
     */
    private function findSegment(ObjectManager $manager)
    {
        $criteria = [
            'name' => LoadNewArrivalProductsSegmentData::NEW_ARRIVALS_SEGMENT_NAME
        ];

        return $manager->getRepository(Segment::class)->findOneBy($criteria);
    }

    /**
     * @return string
     */
    private function getSegmentIdOptionKey()
    {
        return Configuration::getConfigKeyByName(Configuration::NEW_ARRIVALS_PRODUCT_SEGMENT_ID);
    }
}
