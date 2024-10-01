<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Provides WYSIWYG editor functionality.
 */
class WYSIWYGValueType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            $options['field'],
            WYSIWYGType::class,
            ['auto_render' => false, 'entity_class' => $options['entity_class']]
        );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'field' => 'wysiwyg',
            'entity_class' => null,
        ]);
    }
}
