<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class LoadPriceLists extends AbstractFixture implements DependentFixtureInterface
{
    public const PRICE_LIST_1 = 'price_list_1';
    public const PRICE_LIST_2 = 'price_list_2';
    public const PRICE_LIST_3 = 'price_list_3';
    public const PRICE_LIST_4 = 'price_list_4';
    public const PRICE_LIST_5 = 'price_list_5';
    public const PRICE_LIST_6 = 'price_list_6';

    protected static array $data = [
        [
            'name' => 'priceList1',
            'reference' => self::PRICE_LIST_1,
            'currencies' => ['USD', 'EUR', 'AUD', 'CAD'],
            'active' => true,
            'assignmentRule' => null,
        ],
        [
            'name' => 'priceList2',
            'reference' => self::PRICE_LIST_2,
            'currencies' => ['USD'],
            'active' => true,
            'assignmentRule' => 'product.category.id == 2',
        ],
        [
            'name' => 'priceList3',
            'reference' => self::PRICE_LIST_3,
            'currencies' => ['CAD'],
            'active' => true,
            'assignmentRule' => null,
        ],
        [
            'name' => 'priceList4',
            'reference' => self::PRICE_LIST_4,
            'currencies' => ['GBP'],
            'active' => true,
            'assignmentRule' => 'product.sku == "product-1"',
        ],
        [
            'name' => 'priceList5',
            'reference' => self::PRICE_LIST_5,
            'currencies' => ['GBP', 'EUR'],
            'active' => true,
            'assignmentRule' => 'product.category == 1 or product.category.id == 2',
        ],
        [
            'name' => 'priceList6',
            'reference' => self::PRICE_LIST_6,
            'currencies' => ['USD'],
            'active' => false,
            'assignmentRule' => null,
        ],
    ];

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [LoadOrganization::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $now = new \DateTime();

        foreach (static::getPriceListData() as $priceListData) {
            $priceList = new PriceList();

            $priceList->setName($priceListData['name'])
                ->setCurrencies($priceListData['currencies'])
                ->setCreatedAt($now)
                ->setUpdatedAt($now)
                ->setActive($priceListData['active'])
                ->setOrganization($this->getReference('organization'))
                ->setProductAssignmentRule($priceListData['assignmentRule']);

            $manager->persist($priceList);
            $this->setReference($priceListData['reference'], $priceList);
        }

        $manager->flush();
    }

    public static function getPriceListData(): array
    {
        return static::$data;
    }
}
