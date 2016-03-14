<?php

namespace OroB2B\Bundle\CheckoutBundle\DataProvider\Converter;

use Doctrine\Common\Collections\ArrayCollection;

use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;

class CheckoutLineItemsConverter
{
    /**
     * @param array $data
     * @return ArrayCollection
     */
    public function convert(array $data)
    {
        $result = new ArrayCollection();

        foreach ($data as $item) {
            $orderLineItem = new OrderLineItem();
            $orderLineItem->setProduct($item['product']);
            $orderLineItem->setProductSku($item['productSku']);
            $orderLineItem->setQuantity($item['quantity']);
            $orderLineItem->setProductUnit($item['productUnit']);
            $orderLineItem->setProductUnitCode($item['productUnitCode']);
            $orderLineItem->setPrice($item['price']);

            $result->add($orderLineItem);
        }

        return $result;
    }
}
