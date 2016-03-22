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
            $item = $this->normalizeItemData($item);
            $orderLineItem = new OrderLineItem();
            $orderLineItem->setProduct($item['product']);
            $orderLineItem->setProductSku($item['productSku']);
            $orderLineItem->setFreeFormProduct($item['freeFromProduct']);
            $orderLineItem->setQuantity($item['quantity']);
            $orderLineItem->setProductUnit($item['productUnit']);
            $orderLineItem->setProductUnitCode($item['productUnitCode']);
            $orderLineItem->setPrice($item['price']);

            $result->add($orderLineItem);
        }

        return $result;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function normalizeItemData(array $data)
    {
        $data = array_replace([
            'product' => null,
            'productSku' => null,
            'quantity' => 1,
            'productUnit' => null,
            'productUnitCode' => null,
            'price' => null,
            'freeFromProduct' => ''
        ], $data);
        return $data;
    }
}
