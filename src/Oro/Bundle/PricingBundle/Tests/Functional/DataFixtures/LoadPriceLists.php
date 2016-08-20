<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\PricingBundle\Entity\PriceList;

class LoadPriceLists extends AbstractFixture
{
    const PRICE_LIST_1 = 'price_list_1';
    const PRICE_LIST_2 = 'price_list_2';
    const PRICE_LIST_3 = 'price_list_3';
    const PRICE_LIST_4 = 'price_list_4';
    const PRICE_LIST_5 = 'price_list_5';
    const PRICE_LIST_6 = 'price_list_6';
    /**
     * @var array
     */
    protected static $data = [
        [
            'name' => 'priceList1',
            'reference' => self::PRICE_LIST_1,
            'default' => false,
            'currencies' => ['USD', 'EUR', 'AUD', 'CAD'],
            'active' => true,
            'assignmentRule' => null,
        ],
        [
            'name' => 'priceList2',
            'reference' => self::PRICE_LIST_2,
            'default' => false,
            'currencies' => ['USD'],
            'active' => true,
            'assignmentRule' => 'product.category.id == 2',
        ],
        [
            'name' => 'priceList3',
            'reference' => self::PRICE_LIST_3,
            'default' => false,
            'currencies' => ['CAD'],
            'active' => true,
            'assignmentRule' => null,
        ],
        [
            'name' => 'priceList4',
            'reference' => self::PRICE_LIST_4,
            'default' => false,
            'currencies' => ['GBP'],
            'active' => true,
            'assignmentRule' => 'product.sku == "product.1"',
        ],
        [
            'name' => 'priceList5',
            'reference' => self::PRICE_LIST_5,
            'default' => false,
            'currencies' => ['GBP', 'EUR'],
            'active' => true,
            'assignmentRule' => 'product.category == 1 or product.category.id == 2',
        ],
        [
            'name' => 'priceList6',
            'reference' => self::PRICE_LIST_6,
            'default' => false,
            'currencies' => ['USD'],
            'active' => false,
            'assignmentRule' => null,
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
                ->setActive($priceListData['active'])
                ->setProductAssignmentRule($priceListData['assignmentRule']);

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
