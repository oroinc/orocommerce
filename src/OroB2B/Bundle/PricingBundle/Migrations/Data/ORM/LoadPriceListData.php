<?php

namespace OroB2B\Bundle\PricingBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;

class LoadPriceListData extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $priceList = new PriceList();
        $priceList
            ->setDefault(true)
            ->setCurrencies(['USD', 'EUR', 'CAD', 'AUD'])
            ->setName('Default Price List');

        $manager->persist($priceList);
        $manager->flush();

        $this->addReference('default_price_list', $priceList);
    }
}
