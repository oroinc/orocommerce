<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Extension\Stub;

use Symfony\Component\Form\AbstractType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductType;

class ProductTypeStub extends AbstractType
{
    /**
     * @return string
     */
    public function getName()
    {
        return ProductType::NAME;
    }
}
