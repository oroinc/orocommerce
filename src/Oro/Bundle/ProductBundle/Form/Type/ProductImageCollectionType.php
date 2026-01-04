<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Product image collection type.
 */
class ProductImageCollectionType extends AbstractType
{
    public const NAME = 'oro_product_image_collection';

    /**
     * @var ImageTypeProvider
     */
    protected $imageTypeProvider;

    public function __construct(ImageTypeProvider $imageTypeProvider)
    {
        $this->imageTypeProvider = $imageTypeProvider;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'entry_type' => ProductImageType::class,
            'entry_options' => [
                'image_types' => $this->imageTypeProvider->getImageTypes(),
                'allowDelete' => true,
                'allowUpdate' => true,
            ],
            'error_bubbling' => false,
            'show_form_when_empty' => false,
            'row_count_initial' => 0
        ]);
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['imageTypes'] = $options['entry_options']['image_types'];
    }

    #[\Override]
    public function getParent(): ?string
    {
        return CollectionType::class;
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
