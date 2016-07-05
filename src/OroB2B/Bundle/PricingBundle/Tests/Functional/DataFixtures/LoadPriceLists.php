<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;

class LoadPriceLists extends AbstractFixture
{
    /**
     * @var array
     */
    protected static $data = [
        [
            'name' => 'priceList1',
            'reference' => 'price_list_1',
            'default' => false,
            'currencies' => ['USD', 'EUR', 'AUD', 'CAD'],
            'active' => true,
        ],
        [
            'name' => 'priceList2',
            'reference' => 'price_list_2',
            'default' => false,
            'currencies' => ['USD'],
            'active' => true,
        ],
        [
            'name' => 'priceList3',
            'reference' => 'price_list_3',
            'default' => false,
            'currencies' => ['CAD'],
            'active' => true,
        ],
        [
            'name' => 'priceList4',
            'reference' => 'price_list_4',
            'default' => false,
            'currencies' => ['GBP'],
            'active' => true,
        ],
        [
            'name' => 'priceList5',
            'reference' => 'price_list_5',
            'default' => false,
            'currencies' => ['GBP', 'EUR'],
            'active' => true,
        ],
        [
            'name' => 'priceList6',
            'reference' => 'price_list_6',
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

        foreach (self::$data as $priceListData) {
            $priceList = new PriceList();

            $priceList
                ->setName($priceListData['name'])
                ->setDefault($priceListData['default'])
                ->setCurrencies($priceListData['currencies'])
                ->setCreatedAt($now)
                ->setUpdatedAt($now)
                ->setActive($priceListData['active']);

            $manager->persist($priceList);
            $this->setReference($priceListData['reference'], $priceList);
        }

        $manager->flush();
    }

    /**
     * @return array
     */
    public static function getPriceListData()
    {
        return self::$data;
    }
}
