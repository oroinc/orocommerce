<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\ProductBundle\Form\EventSubscriber\ProductImageTypesSubscriber;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductImage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Responsible for image and image types for the product.
 */
class ProductImageType extends AbstractType
{
    const NAME = 'oro_product_image';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'image',
            ImageType::class,
            [
                'allowDelete' => $options['allowDelete'],
                'allowUpdate' => $options['allowUpdate'],
                'constraints' => [new ProductImage()],
            ]
        );

        $builder->add('types', ProductImageTypesType::class, ['image_types' => $options['image_types']]);
        $builder->addEventSubscriber(new ProductImageTypesSubscriber($options['image_types']));
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Oro\Bundle\ProductBundle\Entity\ProductImage',
            'error_bubbling' => false,
            'allow_extra_fields' => true,
            'allowDelete' => true,
            'allowUpdate' => true,
        ]);

        $resolver
            ->setRequired('image_types')
            ->setAllowedTypes('image_types', 'array');
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['imageTypes'] = $options['image_types'];
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
