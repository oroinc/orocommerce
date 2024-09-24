<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Form\Type;

use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\FormBundle\Form\Type\Select2EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type to select a content block.
 */
class ContentBlockSelectType extends AbstractType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => ContentBlock::class,
            'placeholder' => 'oro.cms.contentblock.choose_content_block.label',
            'expanded' => false,
            'multiple' => false,
            'choice_translation_domain' => false,
            'choice_value' => 'id',
            'choice_label' => function (?ContentBlock $contentBlock) {
                return $contentBlock?->getAlias();
            },
        ]);
    }

    #[\Override]
    public function getParent(): string
    {
        return Select2EntityType::class;
    }
}
