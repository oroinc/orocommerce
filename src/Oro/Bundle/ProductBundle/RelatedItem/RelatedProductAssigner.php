<?php

namespace Oro\Bundle\ProductBundle\RelatedItem;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\RelatedItem\ConfigProvider\AbstractRelatedItemConfigProvider;

class RelatedProductAssigner
{
    /**
     * @var AbstractRelatedItemConfigProvider
     */
    private $configProvider;

    /**
     * @param AbstractRelatedItemConfigProvider $configProvider
     */
    public function __construct(AbstractRelatedItemConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * @param Product $productA
     * @param Product $productB
     *
     * @throws \Exception
     */
    public function assignRelation(Product $productA, Product $productB)
    {
        $this->validateRelation($productA, $productB);

        if ($productA->getRelatedToProducts()->contains($productB)) {
            return;
        }

        $productA->addRelatedToProduct($productB);
    }

    /**
     * @param Product $productA
     * @param Product $productB
     */
    public function removeRelation(Product $productA, Product $productB)
    {
        $productA->removeRelatedToProduct($productB);
    }

    /**
     * @param Product $productA
     * @param Product $productB
     * @throws \LogicException when functionality is disabled
     * @throws \InvalidArgumentException when user tries add related product to itself
     * @throws \OverflowException when user tries to add more products that limit allows
     */
    private function validateRelation(Product $productA, Product $productB)
    {
        if (!$this->configProvider->isEnabled()) {
            throw new \LogicException('Related Products functionality is disabled.');
        }

        if ($productA === $productB) {
            throw new \InvalidArgumentException('It is not possible to create relations from product to itself.');
        }

        if ($productA->getRelatedToProducts()->count() >= $this->configProvider->getLimit()) {
            throw new \OverflowException(
                sprintf(
                    'It is not possible to add more related products to %s, because of the limit of relations.',
                    $productA->getName()
                )
            );
        }
    }
}
