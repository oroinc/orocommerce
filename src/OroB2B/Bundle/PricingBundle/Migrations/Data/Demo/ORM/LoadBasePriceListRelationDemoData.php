<?php

namespace OroB2B\Bundle\PricingBundle\Migrations\Data\Demo\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

abstract class LoadBasePriceListRelationDemoData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param EntityManager $manager
     * @param string $name
     * @return PriceList
     */
    protected function getPriceListByName(EntityManager $manager, $name)
    {
        $website = $manager->getRepository('OroB2BPricingBundle:PriceList')->findOneBy(['name' => $name]);

        if (!$website) {
            throw new \LogicException(sprintf('There is no priceList with name "%s" .', $name));
        }

        return $website;
    }

    /**
     * @param EntityManager $manager
     * @param string $name
     * @return Website
     */
    protected function getWebsiteByName(EntityManager $manager, $name)
    {
        $website = $manager->getRepository('OroB2BWebsiteBundle:Website')->findOneBy(['name' => $name]);

        if (!$website) {
            throw new \LogicException(sprintf('There is no website with name "%s" .', $name));
        }

        return $website;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM\LoadAccountDemoData',
            'OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM\LoadAccountGroupDemoData',
            'OroB2B\Bundle\PricingBundle\Migrations\Data\Demo\ORM\LoadPriceListDemoData',
        ];
    }
}
