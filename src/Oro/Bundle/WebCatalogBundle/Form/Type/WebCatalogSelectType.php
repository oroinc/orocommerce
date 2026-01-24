<?php

namespace Oro\Bundle\WebCatalogBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting or creating a web catalog.
 *
 * This form type provides an autocomplete selector for choosing an existing web catalog or creating a new one inline.
 * It is used throughout the application where web catalog selection is required, such as in system configuration
 * or when assigning web catalogs to specific scopes.
 */
class WebCatalogSelectType extends AbstractType
{
    const NAME = 'oro_web_catalog_select';

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => WebCatalogType::class,
                'create_form_route' => 'oro_web_catalog_create',
                'configs' => [
                    'placeholder' => 'oro.webcatalog.form.choose',
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
