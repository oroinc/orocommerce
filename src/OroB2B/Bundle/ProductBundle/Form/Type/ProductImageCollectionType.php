<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;

class ProductImageCollectionType extends AbstractType
{
    const NAME = 'orob2b_product_image_collection';

    /**
     * @var ImageTypeProvider
     */
    protected $imageTypeProvider;

    /**
     * @param ImageTypeProvider $imageTypeProvider
     */
    public function __construct(ImageTypeProvider $imageTypeProvider)
    {
        $this->imageTypeProvider = $imageTypeProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'type' => ProductImageType::NAME,
            'options' => [
                'image_types' => $this->imageTypeProvider->getImageTypes()
            ],
            'error_bubbling' => false,
            'cascade_validation' => true,
            'show_form_when_empty' => false,
            'row_count_initial' => 0

        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['imageTypes'] = $options['options']['image_types'];
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CollectionType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
