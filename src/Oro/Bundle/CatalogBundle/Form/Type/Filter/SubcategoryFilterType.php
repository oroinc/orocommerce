<?php

namespace Oro\Bundle\CatalogBundle\Form\Type\Filter;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubcategoryFilterType extends AbstractType
{
    const NAME = 'oro_type_subcategory_filter';

    const DEFAULT_VALUE = [];

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return FilterType::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'field_type' => 'entity',
                'field_options' => [
                    'multiple' => true,
                    'class' => Category::class,
                ],
                'categories' => []
            ]
        );

        // this normalizer allows to add/override field_options options outside
        $resolver->setNormalizer(
            'field_options',
            function (Options $options, $value) {
                $value['choices'] = $options['categories'] ?? [];

                return $value;
            }
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
