<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\PricingBundle\Entity\PriceAttributePriceList;

class LoadPriceAttributePriceLists extends AbstractFixture
{
    /**
     * @var array
     */
    protected $data = [
        [
            'name' => 'priceAttributePriceList1',
            'fieldName' => 'price_attribute_price_list_1',
            'reference' => 'price_attribute_price_list_1',
            'currencies' => ['USD', 'EUR', 'AUD', 'CAD'],
        ],
        [
            'name' => 'priceAttributePriceList2',
            'fieldName' => 'price_attribute_price_list_2',
            'reference' => 'price_attribute_price_list_2',
            'currencies' => ['USD'],
        ],
        [
            'name' => 'priceAttributePriceList3',
            'fieldName' => 'price_attribute_price_list_3',
            'reference' => 'price_attribute_price_list_3',
            'currencies' => ['CAD'],
        ],
        [
            'name' => 'priceAttributePriceList4',
            'fieldName' => 'price_attribute_price_list_4',
            'reference' => 'price_attribute_price_list_4',
            'currencies' => ['GBP'],
        ],
        [
            'name' => 'priceAttributePriceList5',
            'fieldName' => 'price_attribute_price_list_5',
            'reference' => 'price_attribute_price_list_5',
            'currencies' => ['GBP', 'EUR'],
        ],
        [
            'name' => 'priceAttributePriceList6',
            'fieldName' => 'price_attribute_price_list_6',
            'reference' => 'price_attribute_price_list_6',
            'currencies' => ['USD'],
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $now = new \DateTime();

        foreach ($this->data as $priceAttributePriceListData) {
            $priceAttributePriceList = new PriceAttributePriceList();

            $priceAttributePriceList
                ->setName($priceAttributePriceListData['name'])
                ->setFieldName($priceAttributePriceListData['fieldName'])
                ->setCurrencies($priceAttributePriceListData['currencies'])
                ->setCreatedAt($now)
                ->setUpdatedAt($now);

            $manager->persist($priceAttributePriceList);
            $this->setReference($priceAttributePriceListData['reference'], $priceAttributePriceList);
        }

        $manager->flush();
    }
}
