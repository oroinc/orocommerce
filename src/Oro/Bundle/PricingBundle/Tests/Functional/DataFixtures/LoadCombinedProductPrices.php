<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadCombinedProductPrices extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    const PRICE_PRODUCT_7 = 'product_price.7';

    const PRICE_PRODUCT_8 = 'product_price.8';

    /**
     * @var array
     */
    protected static $data = [
        [
            'product' => 'product-1',
            'priceList' => '1f',
            'qty' => 10,
            'unit' => 'product_unit.liter',
            'price' => 12.2,
            'currency' => 'USD',
            'reference' => 'product_price.1'
        ],
        [
            'product' => 'product-1',
            'priceList' => '1f',
            'qty' => 11,
            'unit' => 'product_unit.bottle',
            'price' => 12.2,
            'currency' => 'EUR',
            'reference' => 'product_price.2'
        ],
        [
            'product' => 'product-2',
            'priceList' => '1f',
            'qty' => 12,
            'unit' => 'product_unit.liter',
            'price' => 12.2,
            'currency' => 'USD',
            'reference' => 'product_price.3'
        ],
        [
            'product' => 'product-2',
            'priceList' => '2f',
            'qty' => 13,
            'unit' => 'product_unit.liter',
            'price' => 12.2,
            'currency' => 'USD',
            'reference' => 'product_price.4'
        ],
        [
            'product' => 'product-2',
            'priceList' => '2f',
            'qty' => 14,
            'unit' => 'product_unit.bottle',
            'price' => 12.2,
            'currency' => 'USD',
            'reference' => 'product_price.5'
        ],
        [
            'product' => 'product-1',
            'priceList' => '2f',
            'qty' => 15,
            'unit' => 'product_unit.liter',
            'price' => 12.2,
            'currency' => 'USD',
            'reference' => 'product_price.6'
        ],
        [
            'product' => 'product-1',
            'priceList' => '1f',
            'qty' => 1,
            'unit' => 'product_unit.liter',
            'price' => 10,
            'currency' => 'USD',
            'reference' => self::PRICE_PRODUCT_7
        ],
        [
            'product' => 'product-2',
            'priceList' => '1f',
            'qty' => 1,
            'unit' => 'product_unit.liter',
            'price' => 20,
            'currency' => 'USD',
            'reference' => self::PRICE_PRODUCT_8
        ],
        [
            'product' => 'product-3',
            'priceList' => '1f',
            'qty' => 1,
            'unit' => 'product_unit.liter',
            'price' => 5,
            'currency' => 'USD',
            'reference' => 'product_price.9'
        ],
        [
            'product' => 'product-1',
            'priceList' => '1f',
            'qty' => 1,
            'unit' => 'product_unit.bottle',
            'price' => 12.2,
            'currency' => 'EUR',
            'reference' => 'product_price.10'
        ],
        [
            'product' => 'product-2',
            'priceList' => '1f',
            'qty' => 14,
            'unit' => 'product_unit.liter',
            'price' => 16.5,
            'currency' => 'EUR',
            'reference' => 'product_price.11'
        ],
        [
            'product' => 'product-2',
            'priceList' => '2f',
            'qty' => 24,
            'unit' => 'product_unit.bottle',
            'price' => 16.5,
            'currency' => 'EUR',
            'reference' => 'product_price.12'
        ],
        [
            'product' => 'product-1',
            'priceList' => '1t_2t_3t',
            'qty' => 1,
            'unit' => 'product_unit.bottle',
            'price' => 1.1,
            'currency' => 'USD',
            'reference' => 'product_price.13'
        ],
        [
            'product' => 'product-1',
            'priceList' => '1t_2t_3t',
            'qty' => 10,
            'unit' => 'product_unit.liter',
            'price' => 1.2,
            'currency' => 'USD',
            'reference' => 'product_price.14'
        ],
        [
            'product' => 'product-4',
            'priceList' => '1f',
            'qty' => 10,
            'unit' => 'product_unit.bottle',
            'price' => 200.5,
            'currency' => 'USD',
            'reference' => 'product_price.15'
        ],
        [
            'product' => 'product-5',
            'priceList' => '1f',
            'qty' => 1,
            'unit' => 'product_unit.bottle',
            'price' => 0,
            'currency' => 'USD',
            'reference' => 'product_price.16'
        ],
        [
            'product' => 'product-1',
            'priceList' => '1f',
            'qty' => 1,
            'unit' => 'product_unit.bottle',
            'price' => 13.1,
            'currency' => 'USD',
            'reference' => 'product_price.17'
        ],
        [
            'product' => 'product-6',
            'priceList' => '1f',
            'qty' => 10,
            'unit' => 'product_unit.bottle',
            'price' => 200.5,
            'currency' => 'USD',
            'reference' => 'product_price.18'
        ],
        [
            'product' => 'продукт-7',
            'priceList' => '1f',
            'qty' => 1,
            'unit' => 'product_unit.bottle',
            'price' => 0,
            'currency' => 'USD',
            'reference' => 'product_price.19'
        ],
        [
            'product' => 'product-1',
            'priceList' => '1f',
            'qty' => 1,
            'unit' => 'product_unit.milliliter',
            'price' => 0,
            'currency' => 'USD',
            'reference' => 'product_price.20'
        ],
        [
            'product' => 'product-2',
            'priceList' => '1f',
            'qty' => 1,
            'unit' => 'product_unit.milliliter',
            'price' => 0,
            'currency' => 'USD',
            'reference' => 'product_price.21'
        ],
        [
            'product' => 'product-3',
            'priceList' => '1f',
            'qty' => 1,
            'unit' => 'product_unit.milliliter',
            'price' => 0,
            'currency' => 'USD',
            'reference' => 'product_price.22'
        ],
        [
            'product' => 'product-4',
            'priceList' => '1f',
            'qty' => 1,
            'unit' => 'product_unit.milliliter',
            'price' => 0,
            'currency' => 'USD',
            'reference' => 'product_price.23'
        ],
        [
            'product' => 'product-5',
            'priceList' => '1f',
            'qty' => 1,
            'unit' => 'product_unit.milliliter',
            'price' => 0,
            'currency' => 'USD',
            'reference' => 'product_price.24'
        ],
        [
            'product' => 'product-1',
            'priceList' => '2t_3t',
            'qty' => 1,
            'unit' => 'product_unit.liter',
            'price' => 0.1,
            'currency' => 'USD',
            'reference' => 'product_price.25'
        ],
        [
            'product' => 'product-1',
            'priceList' => '2t_3t',
            'qty' => 10,
            'unit' => 'product_unit.liter',
            'price' => 1.01,
            'currency' => 'USD',
            'reference' => 'product_price.26'
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $priceLists = [];
        foreach (static::$data as $data) {
            /** @var Product $product */
            $product = $this->getReference($data['product']);

            /** @var CombinedPriceList $cpl */
            $cpl = $this->getReference($data['priceList']);
            $priceLists[$cpl->getId()] = $cpl;

            /** @var ProductUnit $unit */
            $unit = $this->getReference($data['unit']);
            $price = Price::create($data['price'], $data['currency']);

            $productPrice = new CombinedProductPrice();
            $productPrice
                ->setPriceList($cpl)
                ->setUnit($unit)
                ->setQuantity($data['qty'])
                ->setPrice($price)
                ->setProduct($product);

            $manager->persist($productPrice);
            $this->setReference($data['reference'], $productPrice);
        }

        foreach ($priceLists as $cpl) {
            $cpl->setPricesCalculated(true);
            $manager->persist($cpl);
        }

        $manager->flush();

        $this->container->get('event_dispatcher')->dispatch(
            new ReindexationRequestEvent([Product::class], [], [], false),
            ReindexationRequestEvent::EVENT_NAME
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadProductUnitPrecisions::class,
            LoadCombinedPriceLists::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
