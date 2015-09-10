<?php

namespace OroB2B\Bundle\RFPBundle\Form\Extension;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Form\Extension\AbstractPostQuickAddTypeExtension;
use OroB2B\Bundle\RFPBundle\Entity\Request as RFPRequest;
use OroB2B\Bundle\RFPBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPBundle\Entity\RequestProductItem;
use OroB2B\Bundle\RFPBundle\Form\Type\Frontend\RequestType;

class FrontendRequestExtension extends AbstractPostQuickAddTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return RequestType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function addProductToEntity(Product $product, $entity, $quantity)
    {
        if (!$entity instanceof RFPRequest) {
            return;
        }

        /** @var ProductUnit $unit */
        $unit = $product->getUnitPrecisions()->first()->getUnit();

        $requestProductItem = new RequestProductItem();
        $requestProductItem
            ->setProductUnit($unit)
            ->setQuantity($quantity);

        $requestProduct = new RequestProduct();
        $requestProduct
            ->setProduct($product)
            ->addRequestProductItem($requestProductItem);

        $entity->addRequestProduct($requestProduct);
    }
}
