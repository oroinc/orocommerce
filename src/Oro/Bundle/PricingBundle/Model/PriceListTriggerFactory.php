<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\PricingBundle\Model\DTO\PriceListTrigger;
use Oro\Bundle\PricingBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PriceListTriggerFactory
{
    const PRODUCT = 'product';

    /**
     * @param array $products
     * @return PriceListTrigger
     */
    public function create(array $products)
    {
        return new PriceListTrigger($products);
    }

    /**
     * @param array $products
     * @return array
     */
    public function createFromIds(array $products)
    {
        return [self::PRODUCT => array_map([$this, 'getProductIds'], $products)];
    }

    /**
     * @param PriceListTrigger $trigger
     * @return array
     */
    public function triggerToArray(PriceListTrigger $trigger)
    {
        return $this->createFromIds($trigger->getProducts());
    }

    /**
     * @param array|null $data
     * @return PriceListTrigger
     */
    public function createFromArray($data)
    {
        if (!is_array($data)) {
            throw new InvalidArgumentException('Message should not be empty.');
        }

        $resolver = $this->getOptionResolver();
        $data = $resolver->resolve($data);

        return $this->create(array_map([$this, 'getProductIds'], $this->getProducts($data)));
    }

    /**
     * @return OptionsResolver
     */
    private function getOptionResolver()
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired([self::PRODUCT]);
        $resolver->setAllowedTypes(self::PRODUCT, ['array']);

        return $resolver;
    }

    /**
     * @param array $data
     * @return array|int[]
     */
    private function getProducts(array $data)
    {
        return $this->getProductIds($data[self::PRODUCT] ?? []);
    }

    /**
     * @param array $products
     * @return array|int[]
     */
    private function getProductIds(array $products)
    {
        return array_map(
            function ($product) {
                return $product instanceof Product ? $product->getId() : $product;
            },
            $products
        );
    }
}
