<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;

class LoadPriceLists extends AbstractFixture
{
    /**
     * @var array
     */
    protected $data = [
        [
            'name' => 'priceList1',
            'reference' => 'price_list_1',
            'default' => false
        ],
        [
            'name' => 'priceList2',
            'reference' => 'price_list_2',
            'default' => false
        ],
        [
            'name' => 'priceList3',
            'reference' => 'price_list_3',
            'default' => true
        ],
        [
            'name' => 'priceList4',
            'reference' => 'price_list_4',
            'default' => false
        ],
        [
            'name' => 'priceList5',
            'reference' => 'price_list_5',
            'default' => false
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $now = new \DateTime();

        foreach ($this->data as $priceListData) {
            $priceList = new PriceList();

            $priceList
                ->setName($priceListData['name'])
                ->setDefault($priceListData['default'])
                ->setCurrencies(['USD'])
                ->setCreatedAt($now)
                ->setUpdatedAt($now);

            $manager->persist($priceList);
            $this->setReference($priceListData['reference'], $priceList);
        }

        $manager->flush();
    }
}
