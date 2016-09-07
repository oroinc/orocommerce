<?php

namespace Oro\Bundle\ProductBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Exception\InvalidArgumentException;

class ProductMessageFactory
{
    const ID = 'id';
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param Product|null $product
     * @return array
     */
    public function createMessage(Product $product)
    {
        $message[self::ID] = $product->getId();

        return $message;
    }

    /**
     * @param array|null $data
     * @return Product
     */
    public function getProductFromMessage($data)
    {
        $product = $this->registry->getManagerForClass(Product::class)
            ->getRepository(Product::class)
            ->find($data[self::ID]);
        if (!$product) {
            throw new InvalidArgumentException('Product not found.');
        }

        return $product;
    }
}
