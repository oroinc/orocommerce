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
            'reference' => 'price_attribute_price_list_1',
            'default' => false,
            'currencies' => ['USD', 'EUR', 'AUD', 'CAD'],
            'active' => true,
        ],
        [
            'name' => 'priceAttributePriceList2',
            'reference' => 'price_attribute_price_list_2',
            'default' => false,
            'currencies' => ['USD'],
            'active' => true,
        ],
        [
            'name' => 'priceAttributePriceList3',
            'reference' => 'price_attribute_price_list_3',
            'default' => false,
            'currencies' => ['CAD'],
            'active' => true,
        ],
        [
            'name' => 'priceAttributePriceList4',
            'reference' => 'price_attribute_price_list_4',
            'default' => false,
            'currencies' => ['GBP'],
            'active' => true,
        ],
        [
            'name' => 'priceAttributePriceList5',
            'reference' => 'price_attribute_price_list_5',
            'default' => false,
            'currencies' => ['GBP', 'EUR'],
            'active' => true,
        ],
        [
            'name' => 'priceAttributePriceList6',
            'reference' => 'price_attribute_price_list_6',
            'default' => false,
            'currencies' => ['USD'],
            'active' => false,
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
                ->setCurrencies($priceAttributePriceListData['currencies'])
                ->setCreatedAt($now)
                ->setUpdatedAt($now);

            $manager->persist($priceAttributePriceList);
            $this->setReference($priceAttributePriceListData['reference'], $priceAttributePriceList);
        }

        $manager->flush();
    }
}
