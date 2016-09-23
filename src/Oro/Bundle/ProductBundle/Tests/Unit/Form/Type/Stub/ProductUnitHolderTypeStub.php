<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;

class ProductUnitHolderTypeStub extends AbstractType
{
    const NAME = 'oro_stub_product_unit_holder';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('productUnit', ProductUnitSelectionType::NAME, [
                'label' =>  'oro.productunit.entity_label',
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
            'class' => 'Oro\Bundle\ProductBundle\Model\ProductUnitHolderInterface',
            'create_enabled' => true,
            'configs' => [
                'placeholder' => null,
            ],
        ]);
    }
}
