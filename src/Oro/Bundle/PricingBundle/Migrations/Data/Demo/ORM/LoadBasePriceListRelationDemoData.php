<?php

namespace Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides demo fixture loading for price list relation.
 */
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

    protected function getPriceListByName(EntityManagerInterface $manager, string $name): PriceList
    {
        foreach ($this->getPriceLists($manager) as $priceList) {
            if ($priceList->getName() === $name) {
                return $priceList;
            }
        }

        throw new \LogicException(sprintf('There is no priceList with name "%s" .', $name));
    }

    protected function getWebsiteByName(EntityManagerInterface $manager, string $name): Website
    {
        foreach ($this->getWebsites($manager) as $website) {
            if ($website->getName() === $name) {
                return $website;
            }
        }

        throw new \LogicException(sprintf('There is no website with name "%s" .', $name));
    }

    protected function getWebsites(EntityManagerInterface $manager): array
    {
        if (!$this->websites) {
            $this->websites = $manager->getRepository('OroWebsiteBundle:Website')->findAll();
        }

        return $this->websites;
    }

    protected function getPriceLists(EntityManagerInterface $manager): array
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
            'Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadCustomerDemoData',
            'Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadCustomerGroupDemoData',
            'Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM\LoadPriceListDemoData',
        ];
    }
}
