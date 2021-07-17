<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ShippingMethodTypeConfigTypeOptionsStub extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('price', TextType::class)
            ->add('handling_fee', TextType::class)
            ->add('type', TextType::class);
    }
}
