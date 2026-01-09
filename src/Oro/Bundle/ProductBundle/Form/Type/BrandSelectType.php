<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting or creating product brands.
 *
 * This form type extends the entity select or create inline functionality to provide an autocomplete field
 * for brand selection with the ability to create new brands inline.
 */
class BrandSelectType extends AbstractType
{
    public const NAME = 'oro_product_brand_select';

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => BrandType::class,
                'create_form_route' => 'oro_product_brand_create',
                'configs' => [
                    'placeholder' => 'oro.product.brand.form.choose'
                ]
            ]
        );
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

    #[\Override]
    public function getParent(): ?string
    {
        return OroEntitySelectOrCreateInlineType::class;
    }
}
