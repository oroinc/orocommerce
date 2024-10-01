<?php

namespace Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria;

use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;

/**
 * A model for storing product kit price criteria data.
 */
class ProductKitPriceCriteria extends ProductPriceCriteria
{
    /**
     * @var array<int,ProductKitItemPriceCriteria> Indexed by ProductKitItem::$id
     */
    protected array $kitItemsProductsPriceCriteria = [];

    private ?string $identifier = null;

    #[\Override]
    public function getIdentifier(): string
    {
        if (!$this->identifier) {
            $innerCriteriaIdentifiers = array_map(
                static fn (ProductKitItemPriceCriteria $criteria) => $criteria->getIdentifier(),
                $this->kitItemsProductsPriceCriteria
            );
            $this->identifier = sprintf(
                '%s-%s-%s-%s-[%s]',
                $this->getProduct()->getId(),
                $this->getProductUnit()->getCode(),
                $this->getQuantity(),
                $this->getCurrency(),
                implode('-', $innerCriteriaIdentifiers)
            );
        }

        return $this->identifier;
    }

    public function addKitItemProductPriceCriteria(ProductKitItemPriceCriteria $productKitItemPriceCriteria): self
    {
        $kitItemId = $productKitItemPriceCriteria->getKitItem()->getId();
        if (isset($this->kitItemsProductsPriceCriteria[$kitItemId])) {
            throw new \LogicException(
                sprintf(
                    'Product price criteria for the %s #%d is already added and cannot be changed',
                    ProductKitItem::class,
                    $kitItemId
                )
            );
        }

        $this->kitItemsProductsPriceCriteria[$kitItemId] = $productKitItemPriceCriteria;

        // Resets identifier as criteria is changed.
        $this->identifier = null;

        return $this;
    }

    /**
     * @return array<int,ProductKitItemPriceCriteria> Indexed by ProductKitItem::$id
     */
    public function getKitItemsProductsPriceCriteria(): array
    {
        return $this->kitItemsProductsPriceCriteria;
    }
}
