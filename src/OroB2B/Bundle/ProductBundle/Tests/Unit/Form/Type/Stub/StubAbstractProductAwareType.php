<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use OroB2B\Bundle\ProductBundle\Form\Type\AbstractProductAwareType;
use Symfony\Component\Form\FormInterface;

class StubAbstractProductAwareType extends AbstractProductAwareType
{
    const NAME = 'product_aware';

    /** {@inheritdoc} */
    public function getName()
    {
        return self::NAME;
    }

    /** {@inheritdoc} */
    public function getProduct(FormInterface $form)
    {
        return parent::getProduct($form);
    }
}
