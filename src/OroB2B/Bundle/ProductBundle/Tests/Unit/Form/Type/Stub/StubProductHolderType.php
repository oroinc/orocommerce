<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductRemovedSelectType;

class StubProductHolderType extends AbstractType
{
    const NAME = 'orob2b_stub_product_holder';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('product', ProductRemovedSelectType::NAME, [
                'label' =>  'orob2b.product.entity_label',
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
            'class' => 'OroB2B\Bundle\ProductBundle\Model\ProductHolderInterface',
            'create_enabled' => true,
            'configs' => [
                'placeholder' => null,
            ],
        ]);
    }
}
