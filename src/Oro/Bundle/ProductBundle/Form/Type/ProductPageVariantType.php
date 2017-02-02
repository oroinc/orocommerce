<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\ProductBundle\ContentVariantType\ProductPageContentVariantType;
use Oro\Component\WebCatalog\Form\AbstractPageVariantType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProductPageVariantType extends AbstractPageVariantType
{
    const NAME = 'oro_product_page_variant';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'productPageProduct',
                ProductSelectType::NAME,
                [
                    'label' => 'oro.product.entity_label',
                    'required' => true,
                    'constraints' => [new NotBlank()]
                ]
            );

        parent::buildForm($builder, $options);
    }

    /**
     * {@inheritdoc}
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
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPageContentVariantTypeName()
    {
        return ProductPageContentVariantType::TYPE;
    }
}
