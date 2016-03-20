<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Layout\Extension\Theme\Model\ThemeImageType;

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

        /** @var ThemeImageType $imageType */
        foreach ($options['image_types'] as $imageType) {
            $isRadioButton = $imageType->getMaxNumber() === 1;

            $builder->add(
                $imageType->getName(),
                $isRadioButton ? 'radio' : 'checkbox',
                [
                    'label' => $imageType->getLabel(),
                    'value' => 1,
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
            'image_types' => [],
        ]);

        $resolver->setRequired('image_types')
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
