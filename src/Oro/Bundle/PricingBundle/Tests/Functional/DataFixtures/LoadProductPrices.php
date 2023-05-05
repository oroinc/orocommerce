<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadProductPrices extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    const PRODUCT_PRICE_1 = 'product_price.1';
    const PRODUCT_PRICE_2 = 'product_price.2';
    const PRODUCT_PRICE_3 = 'product_price.3';
    const PRODUCT_PRICE_4 = 'product_price.4';
    const PRODUCT_PRICE_5 = 'product_price.5';
    const PRODUCT_PRICE_6 = 'product_price.6';
    const PRODUCT_PRICE_7 = 'product_price.7';
    const PRODUCT_PRICE_8 = 'product_price.8';
    const PRODUCT_PRICE_9 = 'product_price.9';
    const PRODUCT_PRICE_10 = 'product_price.10';
    const PRODUCT_PRICE_11 = 'product_price.11';
    const PRODUCT_PRICE_12 = 'product_price.12';
    const PRODUCT_PRICE_13 = 'product_price.13';
    const PRODUCT_PRICE_14 = 'product_price.14';
    const PRODUCT_PRICE_15 = 'product_price.15';
    const PRODUCT_PRICE_16 = 'product_price.16';
    const PRODUCT_PRICE_17 = 'product_price.17';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    protected $loadedRelations = [];

    /**
     * @var array
     */
    public static $data = [
        self::PRODUCT_PRICE_1 => [
            'product' => 'product-1',
            'priceList' => 'price_list_1',
            'quantity' => 10,
            'unit' => 'product_unit.liter',
            'value' => 12.2,
            'currency' => 'USD'
        ],
        self::PRODUCT_PRICE_2 => [
            'product' => 'product-1',
            'priceList' => 'price_list_1',
            'quantity' => 11,
            'unit' => 'product_unit.bottle',
            'value' => 12.2,
            'currency' => 'EUR'
        ],
        self::PRODUCT_PRICE_3 => [
            'product' => 'product-2',
            'priceList' => 'price_list_1',
            'quantity' => 12,
            'unit' => 'product_unit.liter',
            'value' => 12.2,
            'currency' => 'USD',
        ],
        self::PRODUCT_PRICE_4 => [
            'product' => 'product-2',
            'priceList' => 'price_list_2',
            'quantity' => 13,
            'unit' => 'product_unit.liter',
            'value' => 12.2,
            'currency' => 'USD',
        ],
        self::PRODUCT_PRICE_5 => [
            'product' => 'product-2',
            'priceList' => 'price_list_2',
            'quantity' => 14,
            'unit' => 'product_unit.bottle',
            'value' => 12.2,
            'currency' => 'USD',
        ],
        self::PRODUCT_PRICE_6 => [
            'product' => 'product-1',
            'priceList' => 'price_list_2',
            'quantity' => 15,
            'unit' => 'product_unit.liter',
            'value' => 12.2,
            'currency' => 'USD',
        ],
        self::PRODUCT_PRICE_7 => [
            'product' => 'product-1',
            'priceList' => 'price_list_1',
            'quantity' => 1,
            'unit' => 'product_unit.liter',
            'value' => 10,
            'currency' => 'USD',
        ],
        self::PRODUCT_PRICE_8 => [
            'product' => 'product-2',
            'priceList' => 'price_list_1',
            'quantity' => 1,
            'unit' => 'product_unit.liter',
            'value' => 20,
            'currency' => 'USD',
        ],
        self::PRODUCT_PRICE_9 => [
            'product' => 'product-3',
            'priceList' => 'price_list_1',
            'quantity' => 1,
            'unit' => 'product_unit.liter',
            'value' => 5,
            'currency' => 'USD',
        ],
        self::PRODUCT_PRICE_10 => [
            'product' => 'product-1',
            'priceList' => 'price_list_1',
            'quantity' => 1,
            'unit' => 'product_unit.bottle',
            'value' => 12.2,
            'currency' => 'EUR',
        ],
        self::PRODUCT_PRICE_11 => [
            'product' => 'product-2',
            'priceList' => 'price_list_1',
            'quantity' => 14,
            'unit' => 'product_unit.liter',
            'value' => 16.5,
            'currency' => 'EUR',
        ],
        self::PRODUCT_PRICE_12 => [
            'product' => 'product-2',
            'priceList' => 'price_list_2',
            'quantity' => 24,
            'unit' => 'product_unit.bottle',
            'value' => 16.5,
            'currency' => 'EUR',
        ],
        self::PRODUCT_PRICE_13 => [
            'product' => 'product-2',
            'priceList' => 'first_price_list',
            'quantity' => 1,
            'unit' => 'product_unit.bottle',
            'value' => 17.5,
            'currency' => 'EUR',
        ],
        self::PRODUCT_PRICE_14 => [
            'product' => 'product-3',
            'priceList' => 'first_price_list',
            'quantity' => 1,
            'unit' => 'product_unit.bottle',
            'value' => 20.5,
            'currency' => 'EUR',
        ],
        self::PRODUCT_PRICE_15 => [
            'product' => 'product-2',
            'priceList' => 'price_list_6', // Not active
            'quantity' => 97,
            'unit' => 'product_unit.liter',
            'value' => 15,
            'currency' => 'USD',
        ],
        self::PRODUCT_PRICE_16 => [
            'product' => 'product-2',
            'priceList' => 'price_list_6', // Not active
            'quantity' => 97,
            'unit' => 'product_unit.bottle',
            'value' => 15,
            'currency' => 'EUR',
        ],
        self::PRODUCT_PRICE_17 => [
            'product' => 'product-3',
            'priceList' => 'price_list_3',
            'quantity' => 10,
            'unit' => 'product_unit.bottle',
            'value' => 15,
            'currency' => 'USD',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $priceManager = $this->container->get('oro_pricing.manager.price_manager');
        foreach (static::$data as $reference => $data) {
            /** @var Product $product */
            $product = $this->getReference($data['product']);
            if ($data['priceList'] === 'first_price_list') {
                $priceList = $this->getFirstPriceList($manager);
            } else {
                /** @var PriceList $priceList */
                $priceList = $this->getReference($data['priceList']);
            }
            /** @var ProductUnit $unit */
            $unit = $this->getReference($data['unit']);
            $price = Price::create($data['value'], $data['currency']);

            $productPrice = new ProductPrice();
            $productPrice
                ->setPriceList($priceList)
                ->setUnit($unit)
                ->setQuantity($data['quantity'])
                ->setPrice($price)
                ->setProduct($product);

            $priceManager->persist($productPrice);
            $priceManager->flush();
            $this->setReference($reference, $productPrice);
            $this->referenceRepository->setReferenceIdentity($reference, $productPrice->getId());
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadProductUnitPrecisions::class,
            LoadPriceLists::class,
            LoadOrganization::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    private function getFirstPriceList(ObjectManager $manager): PriceList
    {
        return $manager->getRepository(PriceList::class)
            ->createQueryBuilder('p')
            ->orderBy('p.id')
            ->getQuery()
            ->setMaxResults(1)
            ->getSingleResult();
    }
}
