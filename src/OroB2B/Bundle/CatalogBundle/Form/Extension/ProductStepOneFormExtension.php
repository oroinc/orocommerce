<?php

namespace OroB2B\Bundle\CatalogBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductStepOneType;
use OroB2B\Bundle\CatalogBundle\Form\Type\CategoryTreeType;

class ProductStepOneFormExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ProductStepOneType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'category',
                CategoryTreeType::NAME,
                [
                    'required' => false,
                    'mapped' => false,
                    'label' => 'orob2b.catalog.category.entity_label'
                ]
            )
        ;
    }
}
