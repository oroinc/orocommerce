<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeConfigProvider;

class ProductImageCollectionType extends AbstractType
{
    const NAME = 'orob2b_product_image_collection';

    /**
     * @var ImageTypeConfigProvider
     */
    protected $imageTypeConfigProvider;

    /**
     * @param ImageTypeConfigProvider $imageTypeConfigProvider
     */
    public function __construct(ImageTypeConfigProvider $imageTypeConfigProvider)
    {
        $this->imageTypeConfigProvider = $imageTypeConfigProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $imageTypeConfigs = $this->imageTypeConfigProvider->getConfigs();

        $resolver->setDefaults([
            'type' => ProductImageType::NAME,
            'options' => [
                'image_type_configs' => $imageTypeConfigs
            ],
            'image_type_configs' => $imageTypeConfigs,
        ]);

        $resolver->setAllowedTypes('image_type_configs', 'array');
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
