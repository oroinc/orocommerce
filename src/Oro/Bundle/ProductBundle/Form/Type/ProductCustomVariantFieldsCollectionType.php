<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\ProductBundle\Form\DataTransformer\ProductVariantFieldsTransformer;
use Oro\Bundle\ProductBundle\Form\EventSubscriber\ProductVariantFieldsSubscriber;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\ProductBundle\Provider\CustomFieldProvider;

class ProductCustomVariantFieldsCollectionType extends AbstractType
{
    const NAME = 'oro_product_custom_variant_fields_collection';

    /**
     * @var CustomFieldProvider
     */
    protected $customFieldProvider;

    /**
     * @var string
     */
    protected $productClass;

    /**
     * @param CustomFieldProvider $customFieldProvider
     * @param $productClass
     */
    public function __construct(CustomFieldProvider $customFieldProvider, $productClass)
    {
        $this->customFieldProvider = $customFieldProvider;
        $this->productClass = $productClass;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'type' => 'oro_product_variant_field',
            'multiple' => true,
            'expanded' => true,
            'allow_add' => false,
            'allow_delete' => false,
        ]);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(
            new ProductVariantFieldsSubscriber(
                $options['entry_type'],
                $this->customFieldProvider,
                $this->productClass,
                $options['entry_options']
            )
        );

        $builder->addModelTransformer(new ProductVariantFieldsTransformer());
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return 'oro_collection';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return static::NAME;
    }
}
