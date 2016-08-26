<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\ProductBundle\Form\Type\AbstractProductAwareType;

class AbstractProductAwareTypeStub extends AbstractProductAwareType
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

    /** {@inheritdoc} */
    public function getProductFromView(FormView $view)
    {
        return parent::getProductFromView($view);
    }

    /** {@inheritdoc} */
    public function getProductFromFormOrView(FormInterface $form, FormView $view)
    {
        return parent::getProductFromFormOrView($form, $view);
    }
}
