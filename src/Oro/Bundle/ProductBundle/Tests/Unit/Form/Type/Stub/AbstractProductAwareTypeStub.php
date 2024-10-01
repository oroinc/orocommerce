<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\ProductBundle\Form\Type\AbstractProductAwareType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class AbstractProductAwareTypeStub extends AbstractProductAwareType
{
    const NAME = 'product_aware';

    public function getName()
    {
        return self::NAME;
    }

    #[\Override]
    public function getProduct(FormInterface $form)
    {
        return parent::getProduct($form);
    }

    #[\Override]
    public function getProductFromView(FormView $view)
    {
        return parent::getProductFromView($view);
    }

    #[\Override]
    public function getProductFromFormOrView(FormInterface $form, FormView $view)
    {
        return parent::getProductFromFormOrView($form, $view);
    }
}
