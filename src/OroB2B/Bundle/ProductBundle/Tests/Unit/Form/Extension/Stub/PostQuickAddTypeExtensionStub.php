<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Extension\Stub;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Form\Extension\AbstractPostQuickAddTypeExtension;

class PostQuickAddTypeExtensionStub extends AbstractPostQuickAddTypeExtension
{
    /** @var bool */
    protected $addProductToEntityCalled = false;

    /**
     * {@inheritdoc}
     */
    protected function getItem(Product $product, $entity)
    {
        $this->addProductToEntityCalled = true;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return '';
    }

    /**
     * @return boolean
     */
    public function isAddProductToEntityCalled()
    {
        return $this->addProductToEntityCalled;
    }
}
