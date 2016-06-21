<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ProductBundle\Form\EventSubscriber\ProductImageTypesSubscriber;

class ProductImageType extends AbstractType
{
    const NAME = 'orob2b_product_image';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'image',
            'oro_image',
            [
                'allowDelete' => false,
            ]
        );

        $builder->add(
            'types',
            'hidden'
        );

        $builder->addEventSubscriber(new ProductImageTypesSubscriber($options['image_types']));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'OroB2B\Bundle\ProductBundle\Entity\ProductImage',
            'error_bubbling' => false,
            'allow_extra_fields' => true
        ]);

        $resolver
            ->setRequired('image_types')
            ->setAllowedTypes('image_types', 'array');
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['imageTypes'] = $options['image_types'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
