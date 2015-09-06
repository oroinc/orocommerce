<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use OroB2B\Bundle\ProductBundle\Form\DataTransformer\ProductVariantLinksDataTransformer;

class ProductVariantLinksType extends AbstractType
{
    const NAME = 'orob2b_product_variant_links';

    /**
     * @var string
     */
    private $dataClass;

    /**
     * @param string $dataClass
     * @param ProductVariantLinksDataTransformer $transformer
     */
    public function __construct($dataClass, ProductVariantLinksDataTransformer $transformer = null)
    {
        $this->dataClass = $dataClass;
        $this->transformer = $transformer ?: new ProductVariantLinksDataTransformer();
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'appendVariants',
                'oro_entity_identifier',
                [
                    'class'    => $this->dataClass,
                    'required' => false,
                    'mapped'   => true,
                    'multiple' => true
                ]
            )
            ->add(
                'removeVariants',
                'oro_entity_identifier',
                [
                    'class'    => $this->dataClass,
                    'required' => false,
                    'mapped'   => true,
                    'multiple' => true
                ]
            );

        $builder->addModelTransformer($this->transformer);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}
