<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Extension\Stub;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Form\Extension\AbstractProductDataStorageExtension;

class ProductDataStorageExtensionStub extends AbstractProductDataStorageExtension
{
    /** @var bool */
    protected $addItemCalled = false;

    /**
     * {@inheritdoc}
     */
    protected function addItem(Product $product, $entity, array $itemData = [])
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
