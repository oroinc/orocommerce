<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ProductBundle\Entity\ProductImage;

class ProductImageType extends AbstractType
{
    const NAME = 'orob2b_product_image';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('image', 'oro_image');

        foreach ($options['image_type_configs'] as $imageType => $config) {
            $isRadioButton = $config['max_number'] === 1;

            $builder->add(
                $imageType,
                $isRadioButton ? 'radio' : 'checkbox',
                [
                    'label' => $config['label'],
                    'value' => 1
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'OroB2B\Bundle\ProductBundle\Entity\ProductImage',
            'image_type_configs' => [],
        ]);

        $resolver->setRequired('image_type_configs')
                 ->setAllowedTypes('image_type_configs', 'array');

    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['imageTypeConfigs'] = $options['image_type_configs'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
