<?php

namespace Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadPriceListDemoData extends AbstractFixture implements ContainerAwareInterface
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
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroPricingBundle/Migrations/Data/Demo/ORM/data/price_lists.csv');

        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        $currencies = $this->container->get('oro_currency.config.currency')->getCurrencyList();

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $this->processRow($manager, $row, $currencies);
        }

        fclose($handler);

        $manager->flush();
    }

    protected function processRow(ObjectManager $manager, array $row, array $currencies)
    {
        $priceList = $this->getPriceList($manager, $row['name']);
        $priceList->setDefault((bool)$row['default'])
            ->setCurrencies(array_unique(array_merge($currencies, $priceList->getCurrencies())));

        $manager->persist($priceList);
    }

    /**
     * @param ObjectManager $manager
     * @param string $name
     *
     * @return PriceList
     */
    protected function getPriceList(ObjectManager $manager, $name)
    {
        $priceList = $manager->getRepository('OroPricingBundle:PriceList')->findOneBy(['name' => $name]);

        if (!$priceList) {
            $priceList = new PriceList();
            $priceList->setName($name);
        }

        return $priceList;
    }
}
