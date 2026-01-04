<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductHolderTypeStub extends AbstractType
{
    public const NAME = 'oro_stub_product_holder';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('product', ProductSelectType::class, [
                'label' =>  'oro.product.entity_label',
            ])
        ;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => 'Oro\Bundle\ProductBundle\Model\ProductHolderInterface',
            'create_enabled' => true,
            'configs' => [
                'placeholder' => null,
            ],
        ]);
    }
}
