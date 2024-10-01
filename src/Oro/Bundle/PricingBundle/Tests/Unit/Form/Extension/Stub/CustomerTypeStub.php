<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Extension\Stub;

use Oro\Bundle\CustomerBundle\Form\Type\CustomerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class CustomerTypeStub extends AbstractType
{
    /**
     * @return string
     */
    #[\Override]
    public function getBlockPrefix(): string
    {
        return CustomerType::NAME;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextType::class, ['label' => 'oro.customer.name.label']);
    }
}
