<?php

namespace Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Price attribute demo data
 */
class LoadPriceAttributePriceListDemoData extends AbstractFixture implements ContainerAwareInterface
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
        /** @var Organization $organization */
        $organization = $manager->getRepository(Organization::class)->find(1);
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroPricingBundle/Migrations/Data/Demo/ORM/data/price_attribute.csv');

        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        $currencies = $this->container->get('oro_currency.config.currency')->getCurrencyList();

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $priceAttribute = new PriceAttributePriceList();
            $priceAttribute->setName($row['name'])
                ->setFieldName($row['fieldName'])
                ->setCurrencies($currencies)
                ->setOrganization($organization);

            $manager->persist($priceAttribute);
        }

        fclose($handler);

        $manager->flush();
    }
}
