<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for the TabbedContentItem collection.
 */
class TabbedContentItemCollectionType extends AbstractType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['entry_type' => TabbedContentItemType::class]);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_cms_tabbed_content_item_collection';
    }

    #[\Override]
    public function getParent(): string
    {
        return CollectionType::class;
    }
}
