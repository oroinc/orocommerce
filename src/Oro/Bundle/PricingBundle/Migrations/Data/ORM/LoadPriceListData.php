<?php

namespace Oro\Bundle\PricingBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Migrations\Data\ORM\SetDefaultCurrencyFromLocale;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadPriceListData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    /** @var string */
    const DEFAULT_PRICE_LIST_NAME = 'Default Price List';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            SetDefaultCurrencyFromLocale::class
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $priceList = new PriceList();
        $priceList
            ->setDefault(true)
            ->setCurrencies($this->container->get('oro_currency.config.currency')->getCurrencyList())
            ->setName(self::DEFAULT_PRICE_LIST_NAME);
        $manager->persist($priceList);
        $manager->flush();

        $this->addReference('default_price_list', $priceList);
    }
}
