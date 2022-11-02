<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadProductPricesWithRules extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    const PRODUCT_PRICE_1 = 'product_price_with_rule_1';
    const PRODUCT_PRICE_2 = 'product_price_with_rule_2';
    const PRODUCT_PRICE_3 = 'product_price_with_rule_3';
    const PRODUCT_PRICE_4 = 'product_price_with_rule_4';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $priceManager = $this->container->get('oro_pricing.manager.price_manager');
        foreach ($this->getData() as $reference => $data) {
            /** @var Product $product */
            $product = $this->getReference($data['product']);
            /** @var PriceList $priceList */
            $priceList = $this->getReference($data['priceList']);
            /** @var ProductUnit $unit */
            $unit = $this->getReference($data['unit']);
            /** @var PriceRule $priceRule */
            $priceRule = $this->getReference($data['priceRule']);
            $price = Price::create($data['value'], $data['currency']);

            $productPrice = new ProductPrice();
            $productPrice
                ->setPriceRule($priceRule)
                ->setPriceList($priceList)
                ->setUnit($unit)
                ->setQuantity($data['quantity'])
                ->setPrice($price)
                ->setProduct($product);

            $priceManager->persist($productPrice);
            $priceManager->flush();

            $this->setReference($reference, $productPrice);
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
            LoadPriceRules::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    private function getData(): array
    {
        return [
            self::PRODUCT_PRICE_1 => [
                'priceRule' => LoadPriceRules::PRICE_RULE_1,
                'product' => 'product-1',
                'priceList' => 'price_list_1',
                'quantity' => 5,
                'unit' => 'product_unit.liter',
                'value' => 12.2,
                'currency' => 'USD'
            ],
            self::PRODUCT_PRICE_2 => [
                'priceRule' => LoadPriceRules::PRICE_RULE_2,
                'product' => 'product-2',
                'priceList' => 'price_list_1',
                'quantity' => 10,
                'unit' => 'product_unit.bottle',
                'value' => 10,
                'currency' => 'EUR'
            ],
            self::PRODUCT_PRICE_3 => [
                'priceRule' => LoadPriceRules::PRICE_RULE_2,
                'product' => 'product-2',
                'priceList' => 'price_list_2',
                'quantity' => 14,
                'unit' => 'product_unit.liter',
                'value' => 24.6,
                'currency' => 'USD',
            ],
            self::PRODUCT_PRICE_4 => [
                'priceRule' => LoadPriceRules::PRICE_RULE_3,
                'product' => 'product-2',
                'priceList' => 'price_list_2',
                'quantity' => 37,
                'unit' => 'product_unit.liter',
                'value' => 25.83,
                'currency' => 'EUR',
            ],
        ];
    }
}
