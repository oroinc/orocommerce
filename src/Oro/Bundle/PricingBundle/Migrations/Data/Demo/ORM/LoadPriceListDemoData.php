<?php

namespace Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Loads demo price lists.
 */
class LoadPriceListDemoData extends AbstractFixture implements ContainerAwareInterface
{
    protected ContainerInterface $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate($this->getDataPath());

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

    protected function processRow(ObjectManager $manager, array $row, array $currencies): void
    {
        $priceList = $this->getPriceList($manager, $row['name'], $row['organization']);
        $priceList->setCurrencies(array_unique(array_merge($currencies, $priceList->getCurrencies())));

        $manager->persist($priceList);
    }

    protected function getPriceList(ObjectManager $manager, string $name, ?string $organizationName): PriceList
    {
        $priceList = $manager->getRepository(PriceList::class)->findOneBy(['name' => $name]);

        if (!$priceList) {
            $priceList = new PriceList();
            $priceList->setName($name);

            $organizationRepo = $manager->getRepository(Organization::class);
            $organization = $organizationName
                ? $organizationRepo->findOneBy(['name' => $organizationName])
                : $organizationRepo->getFirst();
            $priceList->setOrganization($organization);
        }

        return $priceList;
    }

    /**
     * Returns path with data should be loaded.
     */
    protected function getDataPath(): string
    {
        return '@OroPricingBundle/Migrations/Data/Demo/ORM/data/price_lists.csv';
    }
}
