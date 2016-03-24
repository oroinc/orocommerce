<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Extension\Stub;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Form\Extension\AbstractProductDataStorageExtension;
use OroB2B\Bundle\ProductBundle\Model\ProductRow;

class ProductDataStorageExtensionStub extends AbstractProductDataStorageExtension
{
    /** @var bool */
    protected $addItemCalled = false;

    /**
     * {@inheritdoc}
     */
    protected function addItem(Product $product, $entity, ProductRow $itemData)
    {
        $this->addItemCalled = true;
    }

    /**
     * @return boolean
     */
    public function isAddItemCalled()
    {
        return $this->addItemCalled;
    }
}
