<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;

class LoadPriceAttributePriceLists extends AbstractFixture
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
        ],
        [
            'name' => 'priceAttributePriceList2',
            'fieldName' => self::PRICE_ATTRIBUTE_PRICE_LIST_2,
            'reference' => self::PRICE_ATTRIBUTE_PRICE_LIST_2,
            'currencies' => ['USD'],
        ],
        [
            'name' => 'priceAttributePriceList3',
            'fieldName' => self::PRICE_ATTRIBUTE_PRICE_LIST_3,
            'reference' => self::PRICE_ATTRIBUTE_PRICE_LIST_3,
            'currencies' => ['CAD'],
        ],
        [
            'name' => 'priceAttributePriceList4',
            'fieldName' => self::PRICE_ATTRIBUTE_PRICE_LIST_4,
            'reference' => self::PRICE_ATTRIBUTE_PRICE_LIST_4,
            'currencies' => ['GBP'],
        ],
        [
            'name' => 'priceAttributePriceList5',
            'fieldName' => self::PRICE_ATTRIBUTE_PRICE_LIST_5,
            'reference' => self::PRICE_ATTRIBUTE_PRICE_LIST_5,
            'currencies' => ['GBP', 'EUR'],
        ],
        [
            'name' => 'priceAttributePriceList6',
            'fieldName' => self::PRICE_ATTRIBUTE_PRICE_LIST_6,
            'reference' => self::PRICE_ATTRIBUTE_PRICE_LIST_6,
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
