<?php

namespace Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\WebsiteBundle\Entity\Website;

abstract class LoadBasePriceListRelationDemoData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    /**
     * @var Website[]
     */
    protected $websites;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var PriceList[]
     */
    protected $priceLists;

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
        foreach ($this->getPriceLists($manager) as $priceList) {
            if ($priceList->getName() === $name) {
                return $priceList;
            }
        }

        throw new \LogicException(sprintf('There is no priceList with name "%s" .', $name));
    }

    /**
     * @param EntityManager $manager
     * @param string $name
     * @return Website
     */
    protected function getWebsiteByName(EntityManager $manager, $name)
    {
        foreach ($this->getWebsites($manager) as $website) {
            if ($website->getName() === $name) {
                return $website;
            }
        }

        throw new \LogicException(sprintf('There is no website with name "%s" .', $name));
    }

    /**
     * @param EntityManager $manager
     * @return Website[]
     */
    protected function getWebsites(EntityManager $manager)
    {
        if (!$this->websites) {
            $this->websites = $manager->getRepository('OroWebsiteBundle:Website')->findAll();
        }

        return $this->websites;
    }

    /**
     * @param EntityManager $manager
     * @return PriceList[]
     */
    protected function getPriceLists(EntityManager $manager)
    {
        if (!$this->priceLists) {
            $this->priceLists = $manager->getRepository('OroPricingBundle:PriceList')->findAll();
        }

        return $this->priceLists;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\AccountBundle\Migrations\Data\Demo\ORM\LoadAccountDemoData',
            'Oro\Bundle\AccountBundle\Migrations\Data\Demo\ORM\LoadAccountGroupDemoData',
            'Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM\LoadPriceListDemoData',
        ];
    }
}
