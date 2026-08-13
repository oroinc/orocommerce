<?php

namespace Oro\Bundle\TaxBundle\OrderTax\Mapper;

use Brick\Math\BigDecimal;
use Oro\Bundle\OrderBundle\Entity\OrderHolderInterface;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\TaxBundle\Model\Taxable;

/**
 * Creates Taxable object from OrderLineItem entity.
 */
class OrderLineItemMapper extends AbstractOrderMapper
{
    /**
     * @param object|OrderLineItem $lineItem
     */
    #[\Override]
    public function map(object $lineItem): Taxable
    {
        $taxable = $this->createTaxable($lineItem);
        if ($lineItem?->getProduct()?->isKit()) {
            $kitPrice = $taxable->getPrice();

            // Need to define tax for each product from a set of products.
            foreach ($lineItem->getKitItemLineItems() as $kitItemLineItem) {
                $kitItemTaxable = $this->createTaxable($kitItemLineItem);
                $kitItemPrice = BigDecimal::of($kitItemTaxable->getPrice())
                    ->multipliedBy($kitItemTaxable->getQuantity());
                $kitPrice = BigDecimal::of($kitPrice)->minus($kitItemPrice);

                $taxable->addItem($kitItemTaxable);
            }

            // Set zero if kit price is negative
            $taxable->setPrice(max($kitPrice->toFloat(), 0));
            $taxable->setKitTaxable(true);
        }

        return $taxable;
    }

    private function createTaxable(OrderHolderInterface $lineItem): Taxable
    {
        $order = $lineItem->getOrder();
        $price = $lineItem->getPrice();

        return (new Taxable())
            ->setIdentifier($lineItem->getId())
            ->setClassName($this->getObjectClass($lineItem))
            ->setQuantity($lineItem->getQuantity() ?? 0)
            ->setOrigin($this->addressProvider->getOriginAddress())
            ->setDestination($this->getDestinationAddress($order))
            ->setTaxationAddress($this->getTaxationAddress($order))
            ->setPrice($price?->getValue() ?? 0)
            ->setCurrency($price?->getCurrency())
            ->setContext($this->getContext($lineItem));
    }

    private function getObjectClass(OrderHolderInterface $lineItem): string
    {
        return $lineItem instanceof OrderLineItem ? OrderLineItem::class : OrderProductKitItemLineItem::class;
    }
}
