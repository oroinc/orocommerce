<?php

namespace Oro\Bundle\ShippingBundle\Context;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;

/**
 * Generates cache key by ShippingContextInterface.
 */
class ShippingContextCacheKeyGenerator
{
    /**
     * @param ShippingContextInterface $context
     * @return string
     */
    public function generateKey(ShippingContextInterface $context)
    {
        $lineItems = array_map(function (ShippingLineItem $item) {
            return $this->lineItemToString($item);
        }, $context->getLineItems()->toArray());

        // if order of line item was changed, hash should not be changed
        usort($lineItems, function ($a, $b) {
            return strcmp(md5($a), md5($b));
        });

        return (string)crc32(implode('', array_merge($lineItems, [
            $context->getCurrency(),
            $context->getPaymentMethod(),
            $this->addressToString($context->getBillingAddress()),
            $this->addressToString($context->getShippingAddress()),
            $this->addressToString($context->getShippingOrigin()),
            $context->getSubtotal() ? $context->getSubtotal()->getValue() : '',
            $context->getSubtotal() ? $context->getSubtotal()->getCurrency() : '',
        ])))
        .($context->getSourceEntity() ? get_class($context->getSourceEntity()) : '')
        .$context->getSourceEntityIdentifier();
    }

    /**
     * @param AddressInterface|null $address
     * @return string
     */
    protected function addressToString(?AddressInterface $address = null)
    {
        return $address ? implode('', [
            $address->getStreet(),
            $address->getStreet2(),
            $address->getCity(),
            $address->getRegionName(),
            $address->getRegionCode(),
            $address->getPostalCode(),
            $address->getCountryName(),
            $address->getCountryIso2(),
            $address->getCountryIso3(),
            $address->getOrganization(),
        ]) : '';
    }

    /**
     * @param ShippingLineItem $item
     * @return string
     */
    protected function lineItemToString(ShippingLineItem $item)
    {
        $strings = [
            $item->getEntityIdentifier(),
            $item->getQuantity(),
            $item->getProductUnitCode()
        ];

        if ($item->getProduct()) {
            $strings[] = $item->getProduct()->getId();
            $strings[] = $item->getProduct()->getSku();
        }

        if ($item->getPrice()) {
            $strings[] = $item->getPrice()->getValue();
            $strings[] = $item->getPrice()->getCurrency();
        }

        if ($item->getWeight()) {
            $strings[] = $item->getWeight()->getValue();
            if ($item->getWeight()->getUnit()) {
                $strings[] = $item->getWeight()->getUnit()->getCode();
            }
        }

        if ($item->getDimensions()) {
            if ($item->getDimensions()->getValue()) {
                $strings[] = $item->getDimensions()->getValue()->getHeight();
                $strings[] = $item->getDimensions()->getValue()->getLength();
                $strings[] = $item->getDimensions()->getValue()->getWidth();
            }
            if ($item->getDimensions()->getUnit()) {
                $strings[] = $item->getDimensions()->getUnit()->getCode();
            }
        }

        $kitItemLineItemsStrings = $this->kitItemLineItemsToStrings($item->getKitItemLineItems());

        return implode('', array_merge($strings, $kitItemLineItemsStrings));
    }

    protected function kitItemLineItemsToStrings(Collection $kitItemLineItems): array
    {
        $kitItemLineItemsStrings = [];
        foreach ($kitItemLineItems as $kitItemLineItem) {
            $kitItemLineItemsStrings[] = $kitItemLineItem->getEntityIdentifier();
            $kitItemLineItemsStrings[] = $kitItemLineItem->getQuantity();
            $kitItemLineItemsStrings[] = $kitItemLineItem->getProductUnitCode();

            if ($kitItemLineItem->getProduct()) {
                $kitItemLineItemProduct = $kitItemLineItem->getProduct();

                $kitItemLineItemsStrings[] = $kitItemLineItemProduct->getId();
                $kitItemLineItemsStrings[] = $kitItemLineItemProduct->getSku();
            }

            if ($kitItemLineItem->getPrice()) {
                $kitItemLineItemPrice = $kitItemLineItem->getPrice();

                $kitItemLineItemsStrings[] = $kitItemLineItemPrice->getValue();
                $kitItemLineItemsStrings[] = $kitItemLineItemPrice->getCurrency();
            }
        }

        return $kitItemLineItemsStrings;
    }
}
