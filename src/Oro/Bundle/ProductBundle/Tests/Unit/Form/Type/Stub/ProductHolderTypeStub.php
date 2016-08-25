<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;

class ProductHolderTypeStub extends AbstractType
{
    const NAME = 'orob2b_stub_product_holder';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('product', ProductSelectType::NAME, [
                'label' =>  'oro.product.entity_label',
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
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
