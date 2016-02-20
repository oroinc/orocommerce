<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use OroB2B\Bundle\ProductBundle\Entity\ProductImage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductImageType extends AbstractType
{
    const NAME = 'orob2b_product_image';

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'image',
                'oro_image',
                []
            )->add(
                'main',
                'choice',
                [
                    'choices' => ['main'],
                    'multiple' => false,
                    'expanded' => true,
                    'label' => null,
                ]
            )->add(
                'additional',
                'choice',
                [
                    'choices' => ['additional'],
                    'multiple' => true,
                    'expanded' => true
                ]
            )->add(
                'thumbnail',
                'choice',
                [
                    'choices' => ['thumbnail'],
                    'multiple' => true,
                    'expanded' => true
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'OroB2B\Bundle\ProductBundle\Entity\ProductImage'
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}
