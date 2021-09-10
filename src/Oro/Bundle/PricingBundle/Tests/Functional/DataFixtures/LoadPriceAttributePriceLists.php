<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class LoadPriceAttributePriceLists extends AbstractFixture implements DependentFixtureInterface
{
    const PRICE_ATTRIBUTE_PRICE_LIST_1 = 'price_attribute_price_list_1';
    const PRICE_ATTRIBUTE_PRICE_LIST_2 = 'price_attribute_price_list_2';
    const PRICE_ATTRIBUTE_PRICE_LIST_3 = 'price_attribute_price_list_3';
    const PRICE_ATTRIBUTE_PRICE_LIST_4 = 'price_attribute_price_list_4';
    const PRICE_ATTRIBUTE_PRICE_LIST_5 = 'price_attribute_price_list_5';
    const PRICE_ATTRIBUTE_PRICE_LIST_6 = 'price_attribute_price_list_6';
    /**
     * @var array
     */
    protected $data = [
        [
            'name' => 'priceAttributePriceList1',
            'fieldName' => self::PRICE_ATTRIBUTE_PRICE_LIST_1,
            'reference' => self::PRICE_ATTRIBUTE_PRICE_LIST_1,
            'currencies' => ['USD', 'EUR', 'AUD', 'CAD'],
            'enabledInExport' => true
        ],
        [
            'name' => 'priceAttributePriceList2',
            'fieldName' => self::PRICE_ATTRIBUTE_PRICE_LIST_2,
            'reference' => self::PRICE_ATTRIBUTE_PRICE_LIST_2,
            'currencies' => ['USD'],
            'enabledInExport' => true
        ],
        [
            'name' => 'priceAttributePriceList3',
            'fieldName' => self::PRICE_ATTRIBUTE_PRICE_LIST_3,
            'reference' => self::PRICE_ATTRIBUTE_PRICE_LIST_3,
            'currencies' => ['CAD'],
            'enabledInExport' => false
        ],
        [
            'name' => 'priceAttributePriceList4',
            'fieldName' => self::PRICE_ATTRIBUTE_PRICE_LIST_4,
            'reference' => self::PRICE_ATTRIBUTE_PRICE_LIST_4,
            'currencies' => ['GBP'],
            'enabledInExport' => false
        ],
        [
            'name' => 'priceAttributePriceList5',
            'fieldName' => self::PRICE_ATTRIBUTE_PRICE_LIST_5,
            'reference' => self::PRICE_ATTRIBUTE_PRICE_LIST_5,
            'currencies' => ['GBP', 'EUR'],
            'enabledInExport' => false
        ],
        [
            'name' => 'priceAttributePriceList6',
            'fieldName' => self::PRICE_ATTRIBUTE_PRICE_LIST_6,
            'reference' => self::PRICE_ATTRIBUTE_PRICE_LIST_6,
            'currencies' => ['USD'],
            'enabledInExport' => false
        ],
    ];

    public function getDependencies()
    {
        return [LoadOrganization::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $now = new \DateTime();
        /** @var Organization $organization */
        $organization = $this->getReference('organization');

        foreach ($this->data as $priceAttributePriceListData) {
            $priceAttributePriceList = new PriceAttributePriceList();

            $priceAttributePriceList
                ->setName($priceAttributePriceListData['name'])
                ->setFieldName($priceAttributePriceListData['fieldName'])
                ->setCurrencies($priceAttributePriceListData['currencies'])
                ->setEnabledInExport($priceAttributePriceListData['enabledInExport'])
                ->setOrganization($organization)
                ->setCreatedAt($now)
                ->setUpdatedAt($now);

            $manager->persist($priceAttributePriceList);
            $this->setReference($priceAttributePriceListData['reference'], $priceAttributePriceList);
        }

        $manager->flush();
    }
}
