<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting CMS pages.
 *
 * Provides an entity select or create inline form type specifically configured for selecting
 * CMS pages with autocomplete functionality and the ability to create new pages inline.
 */
class PageSelectType extends AbstractType
{
    public const NAME = 'oro_cms_page_select';

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => PageType::class,
                'create_form_route' => 'oro_cms_page_create',
                'configs' => [
                    'placeholder' => 'oro.cms.page.form.choose',
                ],
                'attr' => [
                    'class' => 'oro-cms-page-select',
                ],
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
