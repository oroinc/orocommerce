<?php

namespace Oro\Bundle\CatalogBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategorySelectType extends AbstractType
{
    const NAME = 'oro_catalog_category_select';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'oro_category',
                'create_form_route' => 'oro_catalog_category_create',
                'configs' => [
                    'placeholder' => 'oro.catalog.category.form.choose',
                ],
                'attr' => [
                    'class' => 'oro-catalog-category-select',
                ],
            ]
        );
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
    public function getParent()
    {
        return OroEntitySelectOrCreateInlineType::NAME;
    }
}
