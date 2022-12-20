<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\Demo\ORM\Trait;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\DependencyInjection\Configuration as CurrencyConfiguration;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * Trait for loading order line items demo data.
 */
trait OrderLineItemsDemoDataTrait
{
    protected array $products = [];

    public function getOrderLineItem(ObjectManager $manager): OrderLineItem
    {
        $orderLineItem = new OrderLineItem();
        $quantity = random_int(1, 13);
        $price = Price::create(random_int(10, 1000), CurrencyConfiguration::DEFAULT_CURRENCY);
        $product = $this->getProduct($manager);

        return $orderLineItem
            ->setFromExternalSource(random_int(0, 1))
            ->setProduct($product)
            ->setProductName($product->getName())
            ->setProductUnit($this->getProductUnit($product))
            ->setQuantity($quantity)
            ->setPrice($price);
    }

    protected function getProduct(ObjectManager $manager): ?Product
    {
        if (empty($this->products)) {
            $this->products = $manager->getRepository(Product::class)->findAll();
        }

        return $this->products[array_rand($this->products)];
    }

    protected function getProductUnit(Product $product): ?ProductUnit
    {
        $units = $product->getAvailableUnits();

        return $units ? $units[array_rand($units)] : null;
    }
}
